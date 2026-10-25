<?php

/**
 * Khaneh Romantic — What an import step did (counters + a readable log)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support;

final class Report
{
    /** @var array<string, array<string, int>> step => [created, updated, skipped, failed] */
    public array $counts = [];

    /** @var list<array{level: string, step: string, message: string}> */
    public array $log = [];

    public function __construct(public readonly bool $dryRun = false, public readonly ?int $limit = null, private ?\Closure $echo = null) {}

    public function count(string $step, string $what, int $n = 1): void
    {
        $this->counts[$step][$what] = ($this->counts[$step][$what] ?? 0) + $n;
    }

    public function info(string $step, string $message): void
    {
        $this->add('info', $step, $message);
    }

    public function warn(string $step, string $message): void
    {
        $this->add('warning', $step, $message);
    }

    private function add(string $level, string $step, string $message): void
    {
        if (count($this->log) < 2000) {
            $this->log[] = compact('level', 'step', 'message');
        }
        if ($this->echo) {
            ($this->echo)($level, $step, $message);
        }
    }

    public function summary(string $step): string
    {
        $labels = ['created' => 'تازه', 'updated' => 'به‌روز', 'skipped' => 'رد شد', 'failed' => 'خطا', 'no_sku' => 'بدون SKU (پیش‌نویس تا واردکردن SKU)'];

        return collect($this->counts[$step] ?? [])->map(fn ($n, $k) => ($labels[$k] ?? $k).': '.$n)->implode(' · ') ?: 'چیزی نبود';
    }
}
