<?php

/**
 * Khaneh Romantic — An uploaded WordPress database (dump → SQLite) the importer can read from
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportSource extends Model
{
    public const STATUSES = ['uploaded' => 'آپلود شد', 'loading' => 'در حال خواندن', 'ready' => 'آماده', 'failed' => 'خطا'];

    protected $table = 'wp_import_sources';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['state' => 'array', 'detected' => 'array', 'settings' => 'array', 'size' => 'integer', 'offset' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (ImportSource $source) => File::deleteDirectory($source->directory()));
    }

    /** storage/app/private/wp-imports/{id} */
    public function directory(): string
    {
        return Storage::disk('local')->path('wp-imports/'.$this->getKey());
    }

    public function databasePath(): string
    {
        return $this->directory().'/wordpress.sqlite';
    }

    public function dumpPath(): string
    {
        return $this->directory().'/dump.sql';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && is_file($this->databasePath());
    }

    public function progress(): int
    {
        return $this->status === 'ready' ? 100 : ($this->size > 0 ? (int) min(99, floor($this->offset * 100 / $this->size)) : 0);
    }

    /** Point the «wordpress» connection at this database. */
    public function connect(): void
    {
        config(['database.connections.wordpress' => [
            'driver' => 'sqlite',
            'database' => $this->databasePath(),
            'prefix' => (string) $this->prefix,
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('wordpress');
    }
}
