<?php

/**
 * Khaneh Romantic — Admin: import from the WooCommerce site
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * 1) upload the WordPress database export (.sql / .sql.gz / .zip) — or use the WP_DB_* connection,
 * 2) everything is detected and pre-filled (currency, brand plugin and slugs, order statuses…),
 * 3) pick the steps and start. Runs on the queue, or right here when no queue worker is running.
 */

namespace Modules\WordPressImport\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\WordPressImport\Jobs\RunImport;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Models\ImportSource;
use Modules\WordPressImport\Support\Dump\Sources;
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Source;
use Modules\WordPressImport\Support\SourceAnalyzer;
use Throwable;
use UnitEnum;

class WordPressImportPage extends Page
{
    protected string $view = 'wordpressimport::filament.import';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'درون‌ریزی از وردپرس';

    protected static ?string $title = 'درون‌ریزی از وردپرس / ووکامرس';

    protected static ?string $slug = 'wordpress-import';

    /** id of an uploaded database, or "env" for the WP_DB_* connection */
    public string $sourceKey = '';

    /** @var array<string, mixed> */
    public array $settings = [];

    /** @var list<string> */
    public array $selected = [];

    public bool $dryRun = true;

    public ?int $limit = null;

    public ?int $runId = null;

    public function mount(): void
    {
        $this->runId = ImportRun::query()->latest('id')->value('id');
        $latest = ImportSource::query()->whereIn('status', ['ready', 'loading', 'uploaded'])->latest('id')->value('id');
        $this->sourceKey = $latest ? (string) $latest : ($this->envError() === null ? 'env' : '');
        $this->loadSettings();
    }

    /** Every request reads from the chosen database. */
    public function boot(): void
    {
        if (($source = $this->source()) && $source->isReady()) {
            $source->connect();
        }
    }

    // ── sources ──────────────────────────────────────────────────────

    public function source(): ?ImportSource
    {
        return ctype_digit($this->sourceKey) ? ImportSource::find((int) $this->sourceKey) : null;
    }

    /** @return Collection<int, ImportSource> */
    public function sources()
    {
        return ImportSource::query()->latest('id')->get();
    }

    /** null when the WP_DB_* connection works */
    public function envError(): ?string
    {
        $error = Cache::remember('wp-import:env-error', 30, fn () => (new Source('wordpress_env'))->check() ?? '');

        return $error === '' ? null : $error;
    }

    public function selectSource(string $key): void
    {
        $this->sourceKey = $key;
        $this->selected = [];
        $this->boot();
        $this->loadSettings();
    }

    public function uploaded(int $id): void
    {
        $this->selectSource((string) $id);
    }

    public function useIncoming(string $name): void
    {
        $file = collect(Sources::incomingFiles())->firstWhere('name', $name);
        if (! $file) {
            return;
        }
        try {
            $source = Sources::fromFile(Sources::incomingDirectory().'/'.$name, $name, auth()->id());
            $this->selectSource((string) $source->id);
        } catch (Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /** Called again and again by the page while a dump is being read. */
    public function loadTick(): array
    {
        $source = $this->source();
        if (! $source || ! in_array($source->status, ['uploaded', 'loading'], true)) {
            return ['status' => $source?->status];
        }
        try {
            Sources::tick($source, 3.0);
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('خواندن فایل ممکن نشد')->body($e->getMessage())->danger()->send();
        }
        $source->refresh();
        if ($source->status === 'ready') {
            $source->connect();
            $this->loadSettings();
            Notification::make()->title('پایگاه داده خوانده شد؛ تنظیمات را بررسی کنید.')->success()->send();
        }

        return ['status' => $source->status, 'progress' => $source->progress()];
    }

    public function retryLoad(int $id): void
    {
        $source = ImportSource::find($id);
        if ($source && $source->status === 'failed' && $source->file && is_file($source->file)) {
            $source->forceFill(['status' => is_file($source->dumpPath()) && $source->file === $source->dumpPath() && $source->state ? 'loading' : 'uploaded', 'error' => null])->save();
        }
    }

    public function deleteSource(int $id): void
    {
        if (ImportRun::query()->where('source_id', $id)->whereIn('status', ['queued', 'running'])->exists()) {
            Notification::make()->title('این پایگاه داده در حال درون‌ریزی است.')->warning()->send();

            return;
        }
        ImportSource::find($id)?->delete();
        if ($this->sourceKey === (string) $id) {
            $this->selectSource('');
        }
    }

    public function setPrefix(string $prefix): void
    {
        if ($source = $this->source()) {
            Sources::usePrefix($source, $prefix);
            $this->loadSettings();
        }
    }

    // ── detected values & settings ──────────────────────────────────

    public function detected(): ?array
    {
        if ($source = $this->source()) {
            return $source->isReady() ? $source->detected : null;
        }
        if ($this->sourceKey === 'env' && $this->envError() === null) {
            return Cache::remember('wp-import:env-detected', 600, fn () => (new SourceAnalyzer(new Source('wordpress_env')))->analyze());
        }

        return null;
    }

    private function loadSettings(): void
    {
        $detected = $this->detected();
        if (! $detected) {
            $this->settings = [];

            return;
        }
        $saved = ($source = $this->source()) ? ($source->settings ?? []) : Cache::get('wp-import:env-settings', []);
        $this->settings = array_replace(SourceAnalyzer::defaults($detected), $saved);
    }

    public function updatedSettings(mixed $value, string $key): void
    {
        if ($key === 'brand_taxonomy') {
            $this->settings['brand_slugs'] = collect($this->detected()['brands'][$value] ?? [])->mapWithKeys(fn ($b, $id) => [$id => $b['suggested']])->all();
        }
        $this->saveSettings();
    }

    private function saveSettings(): void
    {
        if ($source = $this->source()) {
            $source->forceFill(['settings' => $this->settings])->save();
        } elseif ($this->sourceKey === 'env') {
            Cache::forever('wp-import:env-settings', $this->settings);
        }
    }

    public function resetSettings(): void
    {
        if ($source = $this->source()) {
            $source->forceFill(['settings' => null])->save();
        } else {
            Cache::forget('wp-import:env-settings');
            Cache::forget('wp-import:env-detected');
        }
        $this->loadSettings();
    }

    public function refreshConnection(): void
    {
        Cache::forget('wp-import:env-error');
        Cache::forget('wp-import:env-detected');
        $this->loadSettings();
    }

    // ── steps & runs ─────────────────────────────────────────────────

    /** @return list<array{key: string, label: string, source: ?int, imported: ?int, available: bool, requires: list<string>}> */
    public function stepRows(): array
    {
        $importer = app(Importer::class);
        $ready = $this->detected() !== null;
        $count = function (callable $fn) {
            try {
                return $fn();
            } catch (Throwable) {
                return null;
            }
        };

        return collect($importer->steps())->map(fn ($step, $key) => [
            'key' => $key,
            'label' => $step->label(),
            'source' => $ready && $key !== 'redirects' && $key !== 'links' ? $count(fn () => $step->sourceCount()) : null,
            'imported' => $count(fn () => $step->importedCount()),
            'available' => $importer->available($step),
            'requires' => $step->requires(),
        ])->values()->all();
    }

    public function run(): ?ImportRun
    {
        return $this->runId ? ImportRun::find($this->runId) : null;
    }

    /** @return Collection<int, ImportRun> */
    public function history()
    {
        return ImportRun::query()->latest('id')->limit(8)->get(['id', 'steps', 'dry_run', 'limit', 'status', 'counts', 'created_at', 'finished_at']);
    }

    public function showRun(int $id): void
    {
        $this->runId = $id;
    }

    /** Queued but no worker picked it up. */
    public function waitingForWorker(): bool
    {
        $run = $this->run();

        return $run && $run->status === 'queued' && $run->created_at->lt(now()->subSeconds(10));
    }

    public function start(bool $here = false): void
    {
        if (ImportRun::query()->whereIn('status', ['queued', 'running'])->where('created_at', '>', now()->subHours(6))->exists()) {
            Notification::make()->title('یک درون‌ریزی در حال اجراست؛ صبر کنید تا تمام شود.')->warning()->send();

            return;
        }
        if ($this->detected() === null) {
            Notification::make()->title('اول پایگاه داده‌ی وردپرس را آپلود کنید (یا اتصال WP_DB_* را درست کنید).')->danger()->send();

            return;
        }

        $this->saveSettings();
        $run = ImportRun::create([
            'source_id' => $this->source()?->id,
            'settings' => $this->settings,
            'steps' => array_values(array_intersect(Importer::ORDER, $this->selected)),
            'dry_run' => $this->dryRun,
            'limit' => $this->limit ? max(1, $this->limit) : null,
            'status' => 'queued',
            'user_id' => auth()->id(),
        ]);
        $this->runId = $run->id;

        if ($here) {
            $this->runHere();

            return;
        }
        RunImport::dispatch($run);
        Notification::make()->title($this->dryRun ? 'پیش‌نمایش در صف اجرا قرار گرفت' : 'درون‌ریزی در صف اجرا قرار گرفت')->success()->send();
    }

    /** Run the current queued run inside this request (no queue worker needed). */
    public function runHere(): void
    {
        $run = $this->run();
        if (! $run || $run->status !== 'queued') {
            return;
        }
        ignore_user_abort(true);
        @set_time_limit(0);
        RunImport::dispatchSync($run);
        $run->refresh();
        Notification::make()->title($run->status === 'done' ? 'درون‌ریزی تمام شد' : 'درون‌ریزی با خطا تمام شد')
            ->color($run->status === 'done' ? 'success' : 'danger')->send();
    }

    /** Upload piece size the server accepts (raw body, below post_max_size). */
    public function chunkSize(): int
    {
        $limit = (string) ini_get('post_max_size');
        $bytes = (int) $limit * match (strtolower(substr($limit, -1))) {
            'g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10, default => 1,
        };

        return $bytes > 0 ? max(256 * 1024, min(8 << 20, $bytes - 64 * 1024)) : 8 << 20;
    }
}
