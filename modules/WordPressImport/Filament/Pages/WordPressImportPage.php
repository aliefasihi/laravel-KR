<?php

/**
 * Khaneh Romantic — Admin: import from the WooCommerce site
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Shows the connection, WordPress vs. imported counts per step, starts a run (or a dry run) on
 * the queue, and follows its log live. Large first imports are best run with `php artisan wp:import`.
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
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Source;
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

    /** @var list<string> */
    public array $selected = [];

    public bool $dryRun = true;

    public ?int $limit = null;

    public ?int $runId = null;

    public function mount(): void
    {
        $this->runId = ImportRun::query()->latest('id')->value('id');
    }

    /** @return array{error: ?string, hpos: bool, info: array<string, string>} */
    public function connection(): array
    {
        return Cache::remember('wp-import:connection', 30, function () {
            $wp = app(Source::class);
            $config = config('modules.wordpressimport');
            $error = $wp->check();

            return [
                'error' => $error,
                'hpos' => ! $error && $wp->hpos(),
                'info' => [
                    'دیتابیس' => $config['connection']['driver'].' · '.$config['connection']['host'].' / '.$config['connection']['database'].' (پیشوند '.$config['connection']['prefix'].')',
                    'آدرس سایت قدیم' => (string) $config['legacy_base_url'],
                    'واحد پول ووکامرس' => strtoupper((string) $config['currency']) === 'IRR' ? 'ریال (بدون تبدیل)' : 'تومان (× ۱۰ به ریال)',
                    'پوشه‌ی uploads' => $config['uploads_path'] ?: 'تنظیم نشده — تصاویر از سایت دانلود می‌شوند',
                    'siteurl' => $error ? '—' : (string) $wp->option('siteurl'),
                ],
            ];
        });
    }

    /** @return list<array{key: string, label: string, source: ?int, imported: ?int, available: bool, requires: list<string>}> */
    public function stepRows(): array
    {
        $importer = app(Importer::class);
        $connected = $this->connection()['error'] === null;

        return collect($importer->steps())->map(function ($step, $key) use ($importer, $connected) {
            $count = function (callable $fn) {
                try {
                    return $fn();
                } catch (Throwable) {
                    return null;
                }
            };

            return [
                'key' => $key,
                'label' => $step->label(),
                'source' => $connected ? $count(fn () => $step->sourceCount()) : null,
                'imported' => $count(fn () => $step->importedCount()),
                'available' => $importer->available($step),
                'requires' => $step->requires(),
            ];
        })->values()->all();
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

    public function refreshConnection(): void
    {
        Cache::forget('wp-import:connection');
    }

    public function start(): void
    {
        if (ImportRun::query()->whereIn('status', ['queued', 'running'])->where('created_at', '>', now()->subHours(6))->exists()) {
            Notification::make()->title('یک درون‌ریزی در حال اجراست؛ صبر کنید تا تمام شود.')->warning()->send();

            return;
        }
        if ($error = $this->connection()['error']) {
            Notification::make()->title('اتصال به وردپرس برقرار نیست')->body($error)->danger()->send();

            return;
        }

        $steps = array_values(array_intersect(Importer::ORDER, $this->selected));
        $run = ImportRun::create([
            'steps' => $steps,
            'dry_run' => $this->dryRun,
            'limit' => $this->limit ? max(1, $this->limit) : null,
            'status' => 'queued',
            'user_id' => auth()->id(),
        ]);
        $this->runId = $run->id;
        RunImport::dispatch($run);

        Notification::make()->title($this->dryRun ? 'پیش‌نمایش در صف اجرا قرار گرفت' : 'درون‌ریزی در صف اجرا قرار گرفت')
            ->body(config('queue.default') === 'sync' ? null : 'صف (queue worker) باید روشن باشد.')->success()->send();
    }
}
