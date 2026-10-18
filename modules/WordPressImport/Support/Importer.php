<?php

/**
 * Khaneh Romantic — Runs the WordPress import steps in the right order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Order matters: taxonomies → products → posts → customers → coupons → orders → reviews →
 * redirects → links. Steps whose module is off are skipped. One run at a time (cache lock).
 */

namespace Modules\WordPressImport\Support;

use App\Core\Modules\Modules;
use App\Core\Support\StoreCache;
use Illuminate\Support\Facades\Cache;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Support\Steps\CouponsStep;
use Modules\WordPressImport\Support\Steps\CustomersStep;
use Modules\WordPressImport\Support\Steps\LinksStep;
use Modules\WordPressImport\Support\Steps\OrdersStep;
use Modules\WordPressImport\Support\Steps\PostsStep;
use Modules\WordPressImport\Support\Steps\ProductsStep;
use Modules\WordPressImport\Support\Steps\RedirectsStep;
use Modules\WordPressImport\Support\Steps\ReviewsStep;
use Modules\WordPressImport\Support\Steps\Step;
use Modules\WordPressImport\Support\Steps\TaxonomiesStep;
use RuntimeException;
use Throwable;

class Importer
{
    public const ORDER = ['taxonomies', 'products', 'posts', 'customers', 'coupons', 'orders', 'reviews', 'redirects', 'links'];

    public function __construct(private Source $wp, private MediaImporter $media) {}

    /** @return array<string, Step> in run order */
    public function steps(): array
    {
        $all = [
            new TaxonomiesStep($this->wp, $this->media),
            new ProductsStep($this->wp, $this->media),
            new PostsStep($this->wp, $this->media),
            new CustomersStep($this->wp),
            new CouponsStep($this->wp),
            new OrdersStep($this->wp),
            new ReviewsStep($this->wp),
            new RedirectsStep($this->wp, $this->media),
            new LinksStep($this->wp),
        ];

        return collect($all)->keyBy(fn (Step $s) => $s->key())->all();
    }

    public function available(Step $step): bool
    {
        return collect($step->requires())->every(fn ($m) => Modules::enabled($m));
    }

    /**
     * @param  list<string>  $keys  empty = all
     */
    public function run(array $keys, Report $report, ?ImportRun $run = null): Report
    {
        $lock = Cache::lock('wp-import', 6 * 3600);
        if (! $lock->get()) {
            throw new RuntimeException('یک درون‌ریزی دیگر در حال اجراست.');
        }

        try {
            if ($error = $this->wp->check()) {
                throw new RuntimeException('اتصال به دیتابیس وردپرس برقرار نشد: '.$error);
            }
            $run?->forceFill(['status' => 'running', 'started_at' => now()])->save();

            foreach ($this->steps() as $key => $step) {
                if ($keys !== [] && ! in_array($key, $keys, true)) {
                    continue;
                }
                if (! $this->available($step)) {
                    $report->warn($key, 'ماژول لازم ('.implode('، ', $step->requires()).') خاموش است؛ رد شد.');

                    continue;
                }
                $run?->forceFill(['current_step' => $key])->save();
                $report->info($key, '▶ '.$step->label().($report->dryRun ? ' (پیش‌نمایش)' : ''));
                try {
                    $step->run($report);
                } catch (Throwable $e) {
                    $report->count($key, 'failed');
                    $report->warn($key, 'خطا: '.$e->getMessage());
                    report($e);
                }
                $report->info($key, '✓ '.$report->summary($key));
                $run?->forceFill(['counts' => $report->counts, 'log' => $report->log])->save();
            }

            if (! $report->dryRun) {
                StoreCache::flush();
            }
            $run?->forceFill(['status' => 'done', 'current_step' => null, 'finished_at' => now(), 'counts' => $report->counts, 'log' => $report->log])->save();

            return $report;
        } catch (Throwable $e) {
            $run?->forceFill(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now(), 'counts' => $report->counts, 'log' => $report->log])->save();

            throw $e;
        } finally {
            $lock->release();
        }
    }
}
