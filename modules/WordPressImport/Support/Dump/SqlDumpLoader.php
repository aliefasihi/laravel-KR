<?php

/**
 * Khaneh Romantic — Loads a MySQL dump of the WordPress site into SQLite, a few seconds at a time
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Works with phpMyAdmin, mysqldump, `wp db export` and backup-plugin dumps (.sql, .sql.gz, .zip).
 * No MySQL server is needed: only the tables the importer reads (posts, meta, terms, users,
 * comments, WooCommerce orders…) are kept; logs, sessions and plugin tables are skipped unread.
 *
 * Every tick reads statements until its time budget is used and remembers the byte offset of the
 * next statement, so a 1 GB dump loads through short web requests (or one long CLI run).
 */

namespace Modules\WordPressImport\Support\Dump;

use Illuminate\Support\Facades\File;
use Modules\WordPressImport\Models\ImportSource;
use PDO;
use RuntimeException;
use ZipArchive;

class SqlDumpLoader
{
    /** Tables the importer reads, without the prefix. */
    public const TABLES = [
        'options', 'posts', 'postmeta', 'users', 'usermeta', 'terms', 'term_taxonomy', 'term_relationships', 'termmeta',
        'comments', 'commentmeta', 'woocommerce_attribute_taxonomies', 'woocommerce_order_items', 'woocommerce_order_itemmeta',
        'wc_orders', 'wc_order_addresses', 'wc_order_operational_data', 'wc_orders_meta',
    ];

    /** Lookup indexes the importer relies on (created after loading; dumps' own keys are ignored). */
    private const INDEXES = [
        'options' => [['option_name']],
        'posts' => [['ID'], ['post_type', 'post_status'], ['post_parent'], ['post_name']],
        'postmeta' => [['post_id'], ['meta_key']],
        'users' => [['ID']],
        'usermeta' => [['user_id']],
        'terms' => [['term_id']],
        'term_taxonomy' => [['term_id'], ['taxonomy']],
        'term_relationships' => [['object_id'], ['term_taxonomy_id']],
        'termmeta' => [['term_id']],
        'comments' => [['comment_ID'], ['comment_post_ID'], ['comment_parent']],
        'commentmeta' => [['comment_id']],
        'woocommerce_order_items' => [['order_id']],
        'woocommerce_order_itemmeta' => [['order_item_id']],
        'wc_orders' => [['id'], ['type', 'status'], ['parent_order_id']],
        'wc_order_addresses' => [['order_id']],
        'wc_order_operational_data' => [['order_id']],
        'wc_orders_meta' => [['order_id']],
    ];

    private PDO $pdo;

    /** @var array{tables: array<string, list<string>>, skipped: array<string, int>, delimiter: string, statements: int} */
    private array $state;

    public function __construct(private ImportSource $source) {}

    /**
     * Read for about $seconds (or $maxStatements statements). Returns true when the whole dump is in.
     */
    public function tick(float $seconds = 4.0, ?int $maxStatements = null): bool
    {
        if ($this->source->status === 'ready') {
            return true;
        }
        @set_time_limit(0);

        try {
            if ($this->source->status === 'uploaded') {
                $this->prepare();
            }

            $this->state = ($this->source->state ?? []) + ['tables' => [], 'skipped' => [], 'delimiter' => ';', 'statements' => 0];
            $this->pdo = $this->open();
            $done = $this->read($seconds, $maxStatements);
            if ($done) {
                $this->finish();
            }

            return $done;
        } catch (\Throwable $e) {
            $this->source->forceFill(['status' => 'failed', 'error' => $e->getMessage()])->save();

            throw $e;
        }
    }

    /** Whole dump in one go (CLI). */
    public function loadAll(?\Closure $progress = null): void
    {
        while (! $this->tick(10.0)) {
            $progress && $progress($this->source->progress());
        }
    }

    /** .gz / .zip → plain dump.sql next to the empty SQLite file. */
    private function prepare(): void
    {
        $dir = $this->source->directory();
        File::ensureDirectoryExists($dir);
        $raw = $this->source->file;
        if (! $raw || ! is_file($raw)) {
            throw new RuntimeException('فایل آپلودشده پیدا نشد.');
        }

        $target = $this->source->dumpPath();
        $head = (string) file_get_contents($raw, false, null, 0, 4);
        if (str_starts_with($head, "\x1f\x8b")) {
            $this->gunzip($raw, $target);
        } elseif (str_starts_with($head, "PK\x03\x04")) {
            $this->unzip($raw, $target);
        } elseif ($raw !== $target) {
            rename($raw, $target);
        }
        if ($raw !== $target && is_file($raw)) {
            @unlink($raw);
        }

        @unlink($this->source->databasePath());
        touch($this->source->databasePath());
        $this->source->forceFill([
            'file' => $target, 'database' => 'wp-imports/'.$this->source->id.'/wordpress.sqlite',
            'size' => filesize($target) ?: 0, 'offset' => 0, 'status' => 'loading', 'state' => null, 'error' => null,
        ])->save();
    }

    private function gunzip(string $from, string $to): void
    {
        $in = gzopen($from, 'rb') ?: throw new RuntimeException('فایل gz باز نشد.');
        $out = fopen($to, 'wb');
        while (! gzeof($in)) {
            fwrite($out, (string) gzread($in, 1 << 20));
        }
        gzclose($in);
        fclose($out);
    }

    private function unzip(string $from, string $to): void
    {
        $zip = new ZipArchive;
        if ($zip->open($from) !== true) {
            throw new RuntimeException('فایل zip باز نشد.');
        }
        $entry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (preg_match('/\.sql(\.gz)?$/i', $name) && ! str_starts_with(basename($name), '.')) {
                $entry = $name;
                break;
            }
        }
        if (! $entry) {
            throw new RuntimeException('داخل فایل zip هیچ فایل .sql نیست.');
        }
        $stream = $zip->getStream($entry);
        $tmp = str_ends_with(strtolower($entry), '.gz') ? $to.'.gz' : $to;
        $out = fopen($tmp, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
        $zip->close();
        if ($tmp !== $to) {
            $this->gunzip($tmp, $to);
            @unlink($tmp);
        }
    }

    private function open(): PDO
    {
        $pdo = new PDO('sqlite:'.$this->source->databasePath(), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach (['journal_mode = OFF', 'synchronous = OFF', 'temp_store = MEMORY', 'cache_size = -100000'] as $pragma) {
            $pdo->exec('PRAGMA '.$pragma);
        }

        return $pdo;
    }

    private function read(float $seconds, ?int $maxStatements): bool
    {
        $handle = fopen($this->source->dumpPath(), 'rb') ?: throw new RuntimeException('فایل dump باز نشد.');
        fseek($handle, $this->source->offset);
        $started = microtime(true);
        $count = 0;
        $statement = '';
        $inString = false;
        $offset = $this->source->offset;
        $this->pdo->beginTransaction();

        try {
            while (($line = fgets($handle)) !== false) {
                if ($statement === '' && ! $inString) {
                    $trimmed = ltrim($line);
                    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')
                        || (str_starts_with($trimmed, '/*') && ! str_starts_with($trimmed, '/*!') && str_ends_with(rtrim($trimmed), '*/'))) {
                        $offset = ftell($handle);

                        continue;
                    }
                    if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed, $m)) {
                        $this->state['delimiter'] = $m[1];
                        $offset = ftell($handle);

                        continue;
                    }
                }

                $statement .= $line;
                $this->scan($line, $inString);
                $delimiter = $this->state['delimiter'];
                if ($inString || ! str_ends_with(rtrim($line), $delimiter)) {
                    continue;
                }

                $sql = substr(rtrim($statement), 0, -strlen($delimiter));
                $statement = '';
                if ($delimiter === ';') {
                    $this->handle(trim($sql));
                }
                $offset = ftell($handle);
                $this->state['statements']++;
                $count++;

                if (($maxStatements !== null && $count >= $maxStatements) || microtime(true) - $started >= $seconds) {
                    break;
                }
            }
            $done = feof($handle);
            if ($done && trim($statement) !== '') {
                if ($inString) {
                    throw new RuntimeException('فایل dump ناقص است (انتهای فایل وسط یک مقدار متنی است). دوباره خروجی بگیرید.');
                }
                if ($this->state['delimiter'] === ';') {
                    $this->handle(trim(preg_replace('/;\s*$/', '', $statement)));   // last statement without «;»
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            fclose($handle);

            throw $e;
        }
        fclose($handle);

        $this->source->forceFill(['offset' => $done ? $this->source->size : $offset, 'state' => $this->state])->save();

        return $done;
    }

    /** Track whether a line ends inside a '…' literal (backslash escapes and '' doubling). */
    private function scan(string $line, bool &$inString): void
    {
        $length = strlen($line);
        $i = 0;
        while ($i < $length) {
            if ($inString) {
                $i += strcspn($line, "\\'", $i);
                if ($i >= $length) {
                    break;
                }
                if ($line[$i] === '\\') {
                    $i += 2;

                    continue;
                }
                if ($i + 1 < $length && $line[$i + 1] === "'") {
                    $i += 2;

                    continue;
                }
                $inString = false;
                $i++;
            } else {
                $i += strcspn($line, "'", $i);
                if ($i >= $length) {
                    break;
                }
                $inString = true;
                $i++;
            }
        }
    }

    private function handle(string $sql): void
    {
        if ($sql === '' || str_starts_with($sql, '/*')) {
            return;
        }
        if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^`\s(]+)`?\s*\(/i', $sql, $m)) {
            $open = strlen($m[0]) - 1;
            $close = $this->closingParen($sql, $open);
            $this->create($m[1], substr($sql, $open + 1, $close - $open - 1));

            return;
        }
        if (preg_match('/^(?:INSERT|REPLACE)\s+(?:(?:IGNORE|LOW_PRIORITY|DELAYED|HIGH_PRIORITY)\s+)*INTO\s+`?([^`\s(]+)`?\s*(\([^)]*\))?\s*VALUES\s*/is', $sql, $m)) {
            $this->insert($m[1], $m[2] ?? '', substr($sql, strlen($m[0])));
        }
        // SET, LOCK, DROP, ALTER, views, triggers…: not needed
    }

    public static function keeps(string $table): bool
    {
        foreach (self::TABLES as $suffix) {
            if (str_ends_with($table, $suffix) && preg_match('/^[A-Za-z0-9_]*$/', substr($table, 0, -strlen($suffix)))) {
                return true;
            }
        }

        return false;
    }

    private function create(string $table, string $body): void
    {
        if (! self::keeps($table)) {
            $this->state['skipped'][$table] = 0;

            return;
        }

        $columns = [];
        $definitions = [];
        foreach ($this->splitTopLevel($body) as $part) {
            if (preg_match('/^`([^`]+)`\s+([a-z]+)/i', trim($part), $c)) {
                $columns[] = $c[1];
                $type = strtolower($c[2]);
                $definitions[] = self::quote($c[1]).' '.(match (true) {
                    str_contains($type, 'int') || $type === 'bit' || $type === 'bool' || $type === 'boolean' => 'INTEGER',
                    in_array($type, ['decimal', 'dec', 'numeric', 'float', 'double', 'real'], true) => 'REAL',
                    str_contains($type, 'blob') || $type === 'binary' || $type === 'varbinary' => 'BLOB',
                    default => 'TEXT',
                });
            }
        }
        if (! $columns) {
            throw new RuntimeException("ستون‌های جدول {$table} خوانده نشد.");
        }

        $this->pdo->exec('DROP TABLE IF EXISTS '.self::quote($table));
        $this->pdo->exec('CREATE TABLE '.self::quote($table).' ('.implode(', ', $definitions).')');
        $this->state['tables'][$table] = $columns;
    }

    private function insert(string $table, string $columns, string $values): void
    {
        if (! isset($this->state['tables'][$table])) {
            if (! self::keeps($table)) {
                $this->state['skipped'][$table] = ($this->state['skipped'][$table] ?? 0) + 1;
            }

            return; // skipped table (or rows before its CREATE TABLE, which dumps don't do)
        }

        $list = '';
        if ($columns !== '') {
            $names = array_map(fn ($c) => self::quote(trim($c, " \t\r\n`")), explode(',', trim($columns, '() ')));
            $list = ' ('.implode(', ', $names).')';
        }
        $values = preg_replace('/\s+ON\s+DUPLICATE\s+KEY\s+UPDATE\s+[^\']*$/is', '', $values) ?? $values;

        $this->pdo->exec('INSERT INTO '.self::quote($table).$list.' VALUES '.self::convertValues($values));
    }

    /**
     * MySQL VALUES text → SQLite: backslash escapes, 0x… hex, b'…' bits and charset introducers.
     */
    public static function convertValues(string $values): string
    {
        $converted = preg_replace_callback(
            "/(?<bits>\\bb'[01]*')|'(?<s>(?:[^'\\\\]++|\\\\.|'')*+)'|\\b0x(?<hex>[0-9A-Fa-f]++)|_(?:binary|utf8mb4|utf8mb3|utf8|latin1|ascii)\\b\\s*(?=')/s",
            function (array $m): string {
                if ($m['bits'] !== null) {
                    return (string) bindec(substr($m['bits'], 2, -1) ?: '0');
                }
                if ($m['hex'] !== null) {
                    return "X'".(strlen($m['hex']) % 2 ? '0' : '').$m['hex']."'";
                }

                return $m['s'] !== null ? self::literal($m['s']) : ''; // '' = charset introducer before a literal
            },
            $values,
            -1,
            $count,
            PREG_UNMATCHED_AS_NULL,
        );
        if ($converted === null) {
            throw new RuntimeException('خواندن مقادیر INSERT ممکن نشد (PCRE: '.preg_last_error_msg().').');
        }

        return $converted;
    }

    private static function literal(string $body): string
    {
        if (! str_contains($body, '\\')) {
            return "'".$body."'";   // '' already means ' in both dialects
        }
        $text = preg_replace_callback('/\\\\(.)/s', fn (array $e) => match ($e[1]) {
            '0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1a", 'b' => "\x08",
            "'" => "''", '%' => '\\%', '_' => '\\_',
            default => $e[1],
        }, $body);

        return str_contains($text, "\0")
            ? "'".str_replace("\0", "'||char(0)||'", $text)."'"
            : "'".$text."'";
    }

    /** Position of the «)» closing the «(» at $open (quotes and nesting respected). */
    private function closingParen(string $sql, int $open): int
    {
        $depth = 0;
        $quote = null;
        $length = strlen($sql);
        for ($i = $open; $i < $length; $i++) {
            $ch = $sql[$i];
            if ($quote !== null) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === $quote) {
                    $quote = null;
                }
            } elseif ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
            } elseif ($ch === '(') {
                $depth++;
            } elseif ($ch === ')' && --$depth === 0) {
                return $i;
            }
        }

        return $length;
    }

    /** "a int, b decimal(26,8), KEY k (x,y)" → parts split at depth-0 commas outside quotes */
    private function splitTopLevel(string $body): array
    {
        $parts = [];
        $depth = 0;
        $quote = null;
        $current = '';
        $length = strlen($body);
        for ($i = 0; $i < $length; $i++) {
            $ch = $body[$i];
            if ($quote !== null) {
                if ($ch === '\\') {
                    $current .= $ch.($body[++$i] ?? '');

                    continue;
                }
                if ($ch === $quote) {
                    $quote = null;
                }
            } elseif ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
            } elseif ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }
            $current .= $ch;
        }
        $parts[] = $current;

        return $parts;
    }

    private function finish(): void
    {
        $tables = array_keys($this->state['tables']);
        $prefixes = [];
        foreach ($tables as $table) {
            if (str_ends_with($table, 'options') && in_array(($p = substr($table, 0, -7)).'posts', $tables, true)) {
                $prefixes[$p] = (int) $this->pdo->query('SELECT count(*) FROM '.self::quote($p.'posts'))->fetchColumn();
            }
        }
        if (! $prefixes) {
            throw new RuntimeException('جدول‌های وردپرس (options و posts) در این فایل پیدا نشد. خروجی کامل دیتابیس وردپرس را آپلود کنید.');
        }
        arsort($prefixes);

        foreach ($tables as $table) {
            foreach (self::INDEXES as $suffix => $sets) {
                $prefix = substr($table, 0, -strlen($suffix));
                if (! str_ends_with($table, $suffix) || ! isset($prefixes[$prefix])) {
                    continue;
                }
                foreach ($sets as $i => $columns) {
                    if (array_diff($columns, $this->state['tables'][$table])) {
                        continue;
                    }
                    $this->pdo->exec('CREATE INDEX IF NOT EXISTS '.self::quote('ix_'.$table.'_'.$i).' ON '.self::quote($table)
                        .' ('.implode(', ', array_map(self::quote(...), $columns)).')');
                }
            }
        }
        $this->pdo->exec('ANALYZE');

        $rows = [];
        foreach ($tables as $table) {
            $rows[$table] = (int) $this->pdo->query('SELECT count(*) FROM '.self::quote($table))->fetchColumn();
        }
        @unlink($this->source->dumpPath());

        $this->source->forceFill([
            'status' => 'ready',
            'prefix' => $this->source->prefix ?: array_key_first($prefixes),
            'file' => null,
            'offset' => $this->source->size,
            'state' => $this->state + ['prefixes' => $prefixes, 'rows' => $rows],
        ])->save();
    }

    private static function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
