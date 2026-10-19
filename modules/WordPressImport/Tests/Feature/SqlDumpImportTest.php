<?php

/**
 * Importing from an uploaded database export: the MySQL → SQLite loader (formats, escapes,
 * resuming), chunked upload, detection and settings on the page, and the CLI.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;
use Modules\WordPressImport\Filament\Pages\WordPressImportPage;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Models\ImportSource;
use Modules\WordPressImport\Support\Dump\Sources;
use Tests\TestCase;

class SqlDumpImportTest extends TestCase
{
    use RefreshDatabase;
    use WooStoreFixture;

    private function source(string $sql, string $name = 'dump.sql'): ImportSource
    {
        $path = sys_get_temp_dir().'/'.uniqid('dump-').'-'.$name;
        file_put_contents($path, str_ends_with($name, '.gz') ? gzencode($sql) : $sql);

        return Sources::fromFile($path, $name);
    }

    public function test_loader_reads_phpmyadmin_and_mysqldump_quirks_and_resumes(): void
    {
        $sql = <<<'SQL'
-- phpMyAdmin SQL Dump
# a hash comment
/* a plain block comment */
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE IF NOT EXISTS `kr_options` (
  `option_id` bigint(20) UNSIGNED NOT NULL,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` enum('yes','no','on, off') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='options (core)';

INSERT INTO `kr_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1, 'siteurl', 'https://khanehromantic.com', 'yes'),
(2, 'blogname', 'خانه \'رمانتیک\' ''لوکس''', 'yes'),
(3, 'multi', 'line one\nline two;\r\nwith ; semicolon
and a real newline;', 'no'),
(4, 'slashes', 'C:\\path\\to\\file \"quoted\" 100\% \_x', 'no'),
(5, 'nul', 'a\0b', 'no'),
(6, 'hex', 0x48656C6C6F, 'no'),
(7, 'bin', _binary 'raw', 'no'),
(8, 'empty', '', 'no'),
(9, 'null', NULL, 'no');

CREATE TABLE `kr_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_title` text NOT NULL,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `price` decimal(26,8) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `type_status_date` (`post_type`,`post_status`,`ID`),
  KEY `post_name` (`post_name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
LOCK TABLES `kr_posts` WRITE;
/*!40000 ALTER TABLE `kr_posts` DISABLE KEYS */;
INSERT INTO `kr_posts` VALUES (10,'محصول ۱','product','publish',0,'%d8%aa%d8%a7%d8%a8%d9%87',8450000.5),(11,'it''s (a) post','post','draft',0,'x',-1);
INSERT IGNORE INTO `kr_posts` VALUES (2,'two','post','publish',0,'two',NULL);
/*!40000 ALTER TABLE `kr_posts` ENABLE KEYS */;
UNLOCK TABLES;

ALTER TABLE `kr_posts` ADD KEY `post_parent` (`post_parent`);

CREATE TABLE `kr_actionscheduler_logs` (`log_id` bigint(20) NOT NULL, `message` text NOT NULL) ENGINE=InnoDB;
INSERT INTO `kr_actionscheduler_logs` VALUES (1,'skipped; \'quoted\' ;'),(2,'also skipped');

DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `t1` BEFORE INSERT ON `kr_posts` FOR EACH ROW BEGIN
  SET NEW.post_title = 'x;y';
END */;;
DELIMITER ;

CREATE TABLE `old_options` (`option_id` int, `option_name` varchar(191), `option_value` longtext);
INSERT INTO `old_options` VALUES (1,'siteurl','https://old.example');
CREATE TABLE `old_posts` (`ID` int, `post_title` text);
INSERT INTO `old_posts` VALUES (1,'old')
SQL;
        $source = $this->source($sql);

        // one statement per tick: proves the loader resumes exactly where it stopped
        for ($i = 0; $i < 100 && ! Sources::tick($source, 10, 1)->fresh()->isReady(); $i++) {
            $source->refresh();
            $this->assertNotSame('failed', $source->status, (string) $source->error);
        }
        $source->refresh();
        $this->assertTrue($source->isReady());
        $this->assertSame('kr_', $source->prefix);                     // 2 posts beat old_'s 1
        $this->assertSame(['kr_' => 3, 'old_' => 1], $source->state['prefixes']);
        $this->assertArrayHasKey('kr_actionscheduler_logs', $source->state['skipped']);
        $this->assertFileDoesNotExist($source->dumpPath());           // raw dump removed

        $db = new \PDO('sqlite:'.$source->databasePath());
        $options = $db->query('SELECT option_name, option_value FROM kr_options')->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->assertSame("خانه 'رمانتیک' 'لوکس'", $options['blogname']);
        $this->assertSame("line one\nline two;\r\nwith ; semicolon\nand a real newline;", $options['multi']);
        $this->assertSame('C:\\path\\to\\file "quoted" 100\\% \\_x', $options['slashes']);
        $this->assertSame("a\0b", $options['nul']);
        $this->assertSame('Hello', $options['hex']);
        $this->assertSame('raw', $options['bin']);
        $this->assertSame('', $options['empty']);
        $this->assertNull($options['null']);
        $this->assertSame([2, 10, 11], $db->query('SELECT ID FROM kr_posts ORDER BY ID')->fetchAll(\PDO::FETCH_COLUMN)); // numeric order
        $this->assertSame(8450000.5, (float) $db->query('SELECT price FROM kr_posts WHERE ID = 10')->fetchColumn());
        $this->assertFalse($db->query("SELECT name FROM sqlite_master WHERE name = 'kr_actionscheduler_logs'")->fetchColumn());
        $this->assertNotFalse($db->query("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = 'kr_posts'")->fetchColumn());

        // the other install in the same file can be chosen
        Sources::usePrefix($source->fresh(), 'old_');
        $this->assertSame('https://old.example', $source->fresh()->detected['site']['home']);
    }

    public function test_upload_detect_adjust_settings_and_import(): void
    {
        $wp = $this->site();
        $this->hposOrders($wp);
        // a custom WooCommerce status from a shipping plugin
        $wp->hposOrder(703, ['status' => 'wc-ersal', 'total_amount' => 2950000, 'customer_id' => 500, 'payment_method' => 'cod'],
            ['first_name' => 'علی', 'phone' => '09121112233', 'address_1' => 'خیابان ولیعصر', 'city' => 'تهران', 'state' => 'THR']);
        $wp->item(703, 'line_item', 'تابه', ['_product_id' => '101', '_variation_id' => '102', '_qty' => '1', '_line_subtotal' => '2950000']);
        $wp->option('woocommerce_currency', 'IRT');
        $gz = gzencode(MysqlDumpWriter::dump($wp->db()));

        // the live WordPress connection is gone: everything must come from the file
        config(['database.connections.wordpress_env' => ['driver' => 'sqlite', 'database' => '/nonexistent/x.sqlite', 'prefix' => 'wp_']]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // chunked upload (1 KB pieces), with a wrong offset answered by 409
        $upload = 'test-upload-1234';
        $send = fn (int $offset, string $bytes) => $this->call('POST', '/admin/wordpress-import/upload?'.http_build_query(
            ['upload' => $upload, 'name' => 'khaneh.sql.gz', 'size' => strlen($gz), 'offset' => $offset]), [], [], [],
            ['CONTENT_TYPE' => 'application/octet-stream', 'HTTP_ACCEPT' => 'application/json'], $bytes);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $send(5, 'xx')->assertStatus(409)->assertJson(['offset' => 0]);
        $id = null;
        foreach (str_split($gz, 1024) as $i => $piece) {
            $response = $send($i * 1024, $piece)->assertOk();
            $id = $response->json('id') ?? $id;
        }
        $this->assertNotNull($id);
        $this->postJson('/admin/wordpress-import/upload?upload=other-12345&name=evil.php&size=3&offset=0')->assertStatus(422);
        auth()->logout();
        $this->postJson('/admin/wordpress-import/upload?upload=other-12345&name=a.sql&size=3&offset=0')->assertStatus(401);
        $this->actingAs($admin);

        // the page reads it, detects everything, pre-fills settings
        $page = Livewire::test(WordPressImportPage::class)->call('uploaded', $id)->assertSet('sourceKey', (string) $id);
        for ($i = 0; $i < 20 && ! ImportSource::find($id)->isReady(); $i++) {
            $page->call('loadTick');
        }
        $source = ImportSource::findOrFail($id);
        $this->assertTrue($source->isReady());
        $this->assertSame('wp_', $source->prefix);
        $detected = $source->detected;
        $this->assertSame('https://khanehromantic.com', $detected['site']['home']);
        $this->assertTrue($detected['hpos']);
        $this->assertSame('IRT', $detected['currency']);
        $this->assertSame(['pwb-brand' => 1], $detected['brand_taxonomies']);
        $this->assertSame(2, $detected['counts']['products']);
        $this->assertSame(3, $detected['counts']['orders']);
        $this->assertSame('shipped', $source->settings['order_statuses']['wc-ersal']);   // guessed
        $this->assertSame('goldkish', $source->settings['brand_slugs'][10]);

        $page = Livewire::test(WordPressImportPage::class)
            ->assertSet('sourceKey', (string) $id)
            ->assertSee('khaneh.sql.gz')->assertSee('wc-ersal')->assertSee('گلدکیش')
            ->set('settings.brand_slugs.10', 'Gold Kish!')
            ->set('settings.order_statuses.wc-ersal', 'completed');
        $this->assertSame('Gold Kish!', $source->fresh()->settings['brand_slugs'][10]);   // saved as typed, cleaned on use

        $page->set('dryRun', false)->call('start', true);
        $run = ImportRun::query()->latest('id')->first();
        $this->assertSame('done', $run->status, (string) $run->error.json_encode($run->log, JSON_UNESCAPED_UNICODE));
        $this->assertSame($id, $run->source_id);

        $this->assertSame('gold-kish-gk-1024', Product::query()->where('legacy_post_id', 100)->value('slug'));
        $this->assertSame(3, Order::query()->count());
        $this->assertSame('completed', Order::query()->where('legacy_wp_id', 703)->value('status'));
        $this->assertSame(71_600_000, Order::query()->where('legacy_wp_id', 700)->value('total'));
        $this->assertSame(2, Customer::query()->count());

        // removing the upload deletes its files, keeps what was imported
        $page->call('deleteSource', $id);
        $this->assertDirectoryDoesNotExist($source->directory());
        $this->assertSame(2, Product::query()->count());
    }

    public function test_cli_loads_a_zip_and_imports_from_it(): void
    {
        $wp = $this->site();
        $sql = MysqlDumpWriter::dump($wp->db(), 'phpmyadmin');
        config(['database.connections.wordpress_env' => ['driver' => 'sqlite', 'database' => '/nonexistent/x.sqlite', 'prefix' => 'wp_']]);
        DB::purge('wordpress');

        $zip = sys_get_temp_dir().'/'.uniqid('wp-').'.zip';
        $archive = new \ZipArchive;
        $archive->open($zip, \ZipArchive::CREATE);
        $archive->addFromString('backup/khaneh_wp.sql', $sql);
        $archive->close();

        $this->artisan('wp:import:load', ['file' => $zip, '--keep' => true])->assertSuccessful()->expectsOutputToContain('prefix wp_');
        $this->assertFileExists($zip);
        $this->artisan('wp:import', ['steps' => ['taxonomies', 'products'], '--source' => 'latest'])->assertSuccessful();
        $this->assertSame(2, Product::query()->count());
        $this->artisan('wp:import', ['--source' => '999'])->assertFailed();
    }
}
