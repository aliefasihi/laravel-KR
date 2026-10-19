<?php

/**
 * Khaneh Romantic — Uploaded WordPress databases: register a file, load it, analyse it
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support\Dump;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\WordPressImport\Models\ImportSource;
use Modules\WordPressImport\Support\Source;
use Modules\WordPressImport\Support\SourceAnalyzer;
use RuntimeException;

class Sources
{
    public const EXTENSIONS = '/\.(sql|sql\.gz|gz|zip)$/i';

    /** Files dropped on the server by hand (scp/FTP) wait here: storage/app/private/wp-imports/incoming */
    public static function incomingDirectory(): string
    {
        return Storage::disk('local')->path('wp-imports/incoming');
    }

    /** @return list<array{name: string, size: int, modified: int}> */
    public static function incomingFiles(): array
    {
        $dir = self::incomingDirectory();
        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => preg_match(self::EXTENSIONS, $f->getFilename()))
            ->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'modified' => $f->getMTime()])
            ->sortByDesc('modified')->values()->all();
    }

    /** Register a dump file (moved into the source's own folder). */
    public static function fromFile(string $path, string $name, ?int $userId = null): ImportSource
    {
        if (! is_file($path)) {
            throw new RuntimeException('فایل پیدا نشد: '.$path);
        }
        if (! preg_match(self::EXTENSIONS, $name)) {
            throw new RuntimeException('فقط فایل‌های .sql، .sql.gz و .zip پذیرفته می‌شوند.');
        }

        $source = ImportSource::create(['name' => $name, 'size' => filesize($path) ?: 0, 'status' => 'uploaded', 'user_id' => $userId]);
        File::ensureDirectoryExists($source->directory());
        $target = $source->directory().'/upload'.(preg_match('/(\.sql\.gz|\.gz|\.zip|\.sql)$/i', $name, $m) ? strtolower($m[1]) : '.sql');
        if (! @rename($path, $target)) {
            copy($path, $target);
            @unlink($path);
        }
        $source->forceFill(['file' => $target])->save();

        return $source;
    }

    /** One loading step; analyses the database once it is fully in. */
    public static function tick(ImportSource $source, float $seconds = 3.0, ?int $maxStatements = null): ImportSource
    {
        $done = (new SqlDumpLoader($source))->tick($seconds, $maxStatements);
        if ($done && $source->detected === null) {
            self::analyze($source);
        }

        return $source;
    }

    public static function load(ImportSource $source, ?\Closure $progress = null): ImportSource
    {
        (new SqlDumpLoader($source))->loadAll($progress);
        self::analyze($source);

        return $source;
    }

    /** Detect everything and pre-fill settings (keeps what the admin already changed). */
    public static function analyze(ImportSource $source): ImportSource
    {
        $source->connect();
        $detected = (new SourceAnalyzer(new Source))->analyze();
        $source->forceFill([
            'detected' => $detected,
            'settings' => array_replace(SourceAnalyzer::defaults($detected), $source->settings ?? []),
        ])->save();

        return $source;
    }

    /** Another table prefix (several WordPress installs in one dump). */
    public static function usePrefix(ImportSource $source, string $prefix): ImportSource
    {
        if (! array_key_exists($prefix, $source->state['prefixes'] ?? [])) {
            throw new RuntimeException('این پیشوند در فایل نیست.');
        }
        $source->forceFill(['prefix' => $prefix, 'detected' => null, 'settings' => null])->save();

        return self::analyze($source);
    }
}
