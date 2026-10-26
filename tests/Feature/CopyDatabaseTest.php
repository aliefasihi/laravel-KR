<?php

/**
 * Moving the laptop's SQLite data into the server's database: rows copied, runtime tables left out,
 * panel secrets re-encrypted with the new APP_KEY.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Brand;
use Tests\TestCase;

class CopyDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/khaneh-copy-'.uniqid();
        mkdir($this->dir);
        touch($this->dir.'/database.sqlite');
        config(['database.connections.laptop' => ['driver' => 'sqlite', 'database' => $this->dir.'/database.sqlite', 'prefix' => '', 'foreign_key_constraints' => true]]);
        Artisan::call('migrate', ['--database' => 'laptop', '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('laptop');
        DB::purge('khaneh_source');
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    public function test_rows_and_panel_secrets_move_to_the_server(): void
    {
        $oldKey = Encrypter::generateKey(config('app.cipher'));
        $laptop = DB::connection('laptop');
        $laptop->table('brands')->insert(['id' => 7, 'name_fa' => 'ریتون', 'name_en' => 'Rhyton', 'slug' => 'rhyton', 'created_at' => now(), 'updated_at' => now()]);
        $laptop->table('settings')->insert([
            ['key' => 'bot.bale_token', 'value' => json_encode(Settings::ENCRYPTED.(new Encrypter($oldKey, config('app.cipher')))->encryptString('secret-123')), 'updated_at' => now()],
            ['key' => 'site.home_title', 'value' => json_encode('خانه رمانتیک', JSON_UNESCAPED_UNICODE), 'updated_at' => now()],
        ]);
        $laptop->table('sessions')->insert(['id' => 'abc', 'payload' => 'x', 'last_activity' => time()]);
        file_put_contents($this->dir.'/app-key', 'base64:'.base64_encode($oldKey));

        Brand::query()->create(['name_fa' => 'قدیمی', 'name_en' => 'Old', 'slug' => 'old']);   // replaced, not merged

        $this->artisan('khaneh:copy-database', ['sqlite' => $this->dir.'/database.sqlite', '--old-key-file' => $this->dir.'/app-key', '--force' => true])
            ->assertSuccessful();

        $this->assertSame(['rhyton'], Brand::query()->pluck('slug')->all());
        $this->assertSame(7, Brand::query()->value('id'));
        $this->assertSame('secret-123', Settings::get('bot.bale_token'));
        $this->assertSame('خانه رمانتیک', Settings::get('site.home_title'));
        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_without_the_old_key_secrets_are_blank_and_reported(): void
    {
        DB::connection('laptop')->table('settings')->insert(['key' => 'bot.bale_token', 'value' => json_encode(Settings::ENCRYPTED.'xyz'), 'updated_at' => now()]);

        $this->artisan('khaneh:copy-database', ['sqlite' => $this->dir.'/database.sqlite', '--force' => true])
            ->expectsOutputToContain('enter them again')
            ->assertSuccessful();
        $this->assertNull(Settings::get('bot.bale_token'));
    }
}
