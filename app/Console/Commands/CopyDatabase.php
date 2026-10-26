<?php

/**
 * Khaneh Romantic — Move the laptop's database (SQLite) into the server's database (MariaDB)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan khaneh:copy-database /tmp/khaneh-data/database.sqlite --old-key-file=/tmp/khaneh-data/app-key
 *
 * Run after the first deploy (the server's tables come from its own migrations, so both sides must run the same
 * code). Every table is emptied on the server and filled with the laptop's rows; job queues, sessions and caches
 * are left out. Panel secrets (SMS, bot, AI keys…) are encrypted with the laptop's APP_KEY: with the old key they
 * are re-encrypted with the server's own key, without it they are blank and entered again in the panel.
 * Used by deploy/scripts/import-local-data.sh.
 */

namespace App\Console\Commands;

use App\Core\Settings\Settings;
use App\Core\Support\StoreCache;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CopyDatabase extends Command
{
    protected $signature = 'khaneh:copy-database {sqlite : the laptop\'s database.sqlite}
                            {--old-key-file= : file with the laptop\'s APP_KEY, to keep the panel secrets}
                            {--force : don\'t ask before replacing the data}';

    protected $description = 'Replace this database\'s data with the rows of a local SQLite database';

    /** runtime tables: never carried over */
    private const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'sqlite_sequence'];

    public function handle(): int
    {
        $path = (string) $this->argument('sqlite');
        if (! is_file($path)) {
            $this->components->error("Not found: {$path}");

            return self::FAILURE;
        }

        config(['database.connections.khaneh_source' => ['driver' => 'sqlite', 'database' => realpath($path), 'prefix' => '', 'foreign_key_constraints' => false]]);
        $source = DB::connection('khaneh_source');
        $target = DB::connection();

        // same code on both sides: every migration of the laptop must have run here
        $missing = $source->table('migrations')->pluck('migration')->diff($target->table('migrations')->pluck('migration'));
        if ($missing->isNotEmpty()) {
            $this->components->error('The server is behind the laptop; deploy the latest code first. Missing migrations: '.$missing->take(5)->implode(', '));

            return self::FAILURE;
        }

        $oldKey = $this->oldKey();
        if ($oldKey === false) {
            return self::FAILURE;
        }

        $here = collect($target->getSchemaBuilder()->getTableListing(schemaQualified: false));
        $tables = collect($source->getSchemaBuilder()->getTableListing(schemaQualified: false))
            ->reject(fn (string $t) => in_array($t, self::SKIP, true) || ! $here->contains($t))->values();

        if (! $this->option('force') && ! $this->confirm("All data of «{$target->getDatabaseName()}» ({$tables->count()} tables) will be replaced. Continue?")) {
            return self::FAILURE;
        }

        $target->getSchemaBuilder()->disableForeignKeyConstraints();
        try {
            $rows = $this->withProgressBar($tables, fn (string $table) => $this->copy($source, $target, $table));
        } finally {
            $target->getSchemaBuilder()->enableForeignKeyConstraints();
        }
        $this->newLine(2);

        // a copy is only done when every table has the same number of rows
        $wrong = $tables->filter(fn (string $t) => $source->table($t)->count() !== $target->table($t)->count());
        if ($wrong->isNotEmpty()) {
            $this->components->error('Row counts differ: '.$wrong->implode(', '));

            return self::FAILURE;
        }

        [$kept, $blank] = $this->reseal($oldKey);
        Cache::forget(Settings::CACHE_KEY);
        StoreCache::flush();

        $this->components->info("{$tables->count()} tables copied.");
        $this->components->twoColumnDetail('Products', (string) $target->table('products')->count());
        $this->components->twoColumnDetail('Orders', (string) $target->table('orders')->count());
        $this->components->twoColumnDetail('Customers', (string) $target->table('users')->count());
        $this->components->twoColumnDetail('Panel secrets kept', (string) $kept);
        if ($blank > 0) {
            $this->components->warn("{$blank} panel secrets could not be read (no or wrong old key): enter them again in تنظیمات فروشگاه.");
        }

        return self::SUCCESS;
    }

    private function copy(Connection $source, Connection $target, string $table): void
    {
        $columns = array_values(array_intersect(
            $source->getSchemaBuilder()->getColumnListing($table),
            $target->getSchemaBuilder()->getColumnListing($table),
        ));

        $target->table($table)->truncate();
        $source->table($table)->select($columns)->orderByRaw('rowid')->chunk(500, function ($rows) use ($target, $table) {
            $target->table($table)->insert($rows->map(fn ($row) => (array) $row)->all());
        });
    }

    /** null = no old key given, false = unreadable file */
    private function oldKey(): Encrypter|null|false
    {
        $file = $this->option('old-key-file');
        if (! $file) {
            return null;
        }
        $key = trim((string) @file_get_contents($file));
        if ($key === '') {
            $this->components->error("Can't read the old key from {$file}");

            return false;
        }
        $key = Str::startsWith($key, 'base64:') ? base64_decode(substr($key, 7)) : $key;

        return new Encrypter($key, config('app.cipher'));
    }

    /** Panel secrets: decrypted with the laptop's key, encrypted again with this server's. @return array{int, int} */
    private function reseal(?Encrypter $old): array
    {
        $kept = $blank = 0;
        foreach (DB::table('settings')->get() as $row) {
            $value = json_decode((string) $row->value, true);
            if (! is_string($value) || ! str_starts_with($value, Settings::ENCRYPTED)) {
                continue;
            }
            try {
                $plain = $old?->decryptString(substr($value, strlen(Settings::ENCRYPTED)));
            } catch (DecryptException) {
                $plain = null;
            }
            if ($plain === null) {
                $blank++;

                continue;
            }
            DB::table('settings')->where('key', $row->key)->update(['value' => json_encode(Settings::ENCRYPTED.Crypt::encryptString($plain), JSON_UNESCAPED_UNICODE)]);
            $kept++;
        }

        return [$kept, $blank];
    }
}
