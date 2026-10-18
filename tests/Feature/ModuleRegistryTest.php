<?php

/**
 * Modules can be switched on/off like WordPress plugins, respecting core status and dependencies.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    use RefreshDatabase;

    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backup = ModuleRegistry::file().'.test-backup';
        if (is_file(ModuleRegistry::file())) {
            rename(ModuleRegistry::file(), $this->backup);
        }
    }

    protected function tearDown(): void
    {
        @unlink(ModuleRegistry::file());
        if (is_file($this->backup)) {
            rename($this->backup, ModuleRegistry::file());
        }
        parent::tearDown();
    }

    public function test_metadata_and_defaults(): void
    {
        $registry = new ModuleRegistry;

        $this->assertSame('کاتالوگ محصولات', $registry->all()['Catalog']['title']);
        $this->assertTrue($registry->all()['Catalog']['core']);
        $this->assertTrue($registry->enabled('FileManager'));
        $this->assertContains('Modules\FileManager\Providers\FileManagerServiceProvider', $registry->providers());
    }

    public function test_core_modules_cannot_be_disabled(): void
    {
        $this->expectException(RuntimeException::class);
        (new ModuleRegistry)->disable('Catalog');
    }

    public function test_disable_and_enable_with_dependencies(): void
    {
        $registry = new ModuleRegistry;

        // Payment requires Order: Order can't go while Payment is on
        try {
            $registry->disable('Order');
            $this->fail('Order should be protected by Payment');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('درگاه‌های پرداخت', $e->getMessage());
        }

        $registry->disable('Payment');
        $registry->disable('Order');
        $this->assertFalse((new ModuleRegistry)->enabled('Order'));
        $this->assertNotContains('Modules\Order\Providers\OrderServiceProvider', (new ModuleRegistry)->providers());

        // Payment can't come back before Order
        $this->expectException(RuntimeException::class);
        (new ModuleRegistry)->enable('Payment');
    }

    public function test_admin_page_lists_modules(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/modules')
            ->assertOk()
            ->assertSee('فایل منیجر')
            ->assertSee('هسته');
    }
}
