<?php

/**
 * A tiny WooCommerce database in SQLite (connection «wordpress», prefix wp_) for importer tests.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class FakeWordPress
{
    private int $metaId = 1;

    public static function boot(bool $hpos = true, string $database = ':memory:'): self
    {
        config(['database.connections.wordpress' => [
            'driver' => 'sqlite', 'database' => $database, 'prefix' => 'wp_', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('wordpress');
        $wp = new self;
        $wp->schema($hpos);

        return $wp;
    }

    public function db()
    {
        return DB::connection('wordpress');
    }

    private function schema(bool $hpos): void
    {
        $s = $this->db()->getSchemaBuilder();
        $s->create('options', function (Blueprint $t) {
            $t->id('option_id');
            $t->string('option_name')->unique();
            $t->longText('option_value');
            $t->string('autoload')->default('yes');
        });
        $s->create('posts', function (Blueprint $t) {
            $t->id('ID');
            $t->unsignedBigInteger('post_author')->default(1);
            $t->dateTime('post_date')->nullable();
            $t->dateTime('post_date_gmt')->nullable();
            $t->longText('post_content')->default('');
            $t->text('post_title')->default('');
            $t->text('post_excerpt')->default('');
            $t->string('post_status')->default('publish');
            $t->string('post_name')->default('');
            $t->unsignedBigInteger('post_parent')->default(0);
            $t->string('guid')->default('');
            $t->integer('menu_order')->default(0);
            $t->string('post_type')->default('post');
        });
        foreach (['postmeta' => 'post_id', 'termmeta' => 'term_id', 'commentmeta' => 'comment_id', 'woocommerce_order_itemmeta' => 'order_item_id'] as $table => $fk) {
            $s->create($table, function (Blueprint $t) use ($fk) {
                $t->id('meta_id');
                $t->unsignedBigInteger($fk);
                $t->string('meta_key')->nullable();
                $t->longText('meta_value')->nullable();
            });
        }
        $s->create('usermeta', function (Blueprint $t) {
            $t->id('umeta_id');
            $t->unsignedBigInteger('user_id');
            $t->string('meta_key')->nullable();
            $t->longText('meta_value')->nullable();
        });
        $s->create('users', function (Blueprint $t) {
            $t->id('ID');
            $t->string('user_login');
            $t->string('user_nicename')->default('');
            $t->string('user_email')->default('');
            $t->dateTime('user_registered')->nullable();
        });
        $s->create('terms', function (Blueprint $t) {
            $t->id('term_id');
            $t->string('name');
            $t->string('slug');
        });
        $s->create('term_taxonomy', function (Blueprint $t) {
            $t->id('term_taxonomy_id');
            $t->unsignedBigInteger('term_id');
            $t->string('taxonomy');
            $t->longText('description')->default('');
            $t->unsignedBigInteger('parent')->default(0);
            $t->integer('count')->default(0);
        });
        $s->create('term_relationships', function (Blueprint $t) {
            $t->unsignedBigInteger('object_id');
            $t->unsignedBigInteger('term_taxonomy_id');
            $t->integer('term_order')->default(0);
        });
        $s->create('comments', function (Blueprint $t) {
            $t->id('comment_ID');
            $t->unsignedBigInteger('comment_post_ID');
            $t->string('comment_author')->default('');
            $t->string('comment_author_email')->default('');
            $t->string('comment_author_IP')->default('');
            $t->dateTime('comment_date_gmt')->nullable();
            $t->text('comment_content');
            $t->string('comment_approved')->default('1');
            $t->string('comment_type')->default('comment');
            $t->unsignedBigInteger('comment_parent')->default(0);
            $t->unsignedBigInteger('user_id')->default(0);
        });
        $s->create('woocommerce_attribute_taxonomies', function (Blueprint $t) {
            $t->id('attribute_id');
            $t->string('attribute_name');
            $t->string('attribute_label');
        });
        $s->create('woocommerce_order_items', function (Blueprint $t) {
            $t->id('order_item_id');
            $t->text('order_item_name');
            $t->string('order_item_type');
            $t->unsignedBigInteger('order_id');
        });

        if ($hpos) {
            $s->create('wc_orders', function (Blueprint $t) {
                $t->unsignedBigInteger('id')->primary();
                $t->string('status');
                $t->string('currency')->default('IRT');
                $t->string('type')->default('shop_order');
                $t->decimal('total_amount', 26, 8)->default(0);
                $t->unsignedBigInteger('customer_id')->default(0);
                $t->string('billing_email')->nullable();
                $t->dateTime('date_created_gmt')->nullable();
                $t->unsignedBigInteger('parent_order_id')->default(0);
                $t->string('payment_method')->nullable();
                $t->text('payment_method_title')->nullable();
                $t->string('ip_address')->nullable();
                $t->text('customer_note')->nullable();
            });
            $s->create('wc_order_addresses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id');
                $t->string('address_type');
                foreach (['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone'] as $c) {
                    $t->text($c)->nullable();
                }
            });
            $s->create('wc_order_operational_data', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id');
                $t->dateTime('date_paid_gmt')->nullable();
                $t->dateTime('date_completed_gmt')->nullable();
                $t->decimal('shipping_total_amount', 26, 8)->nullable();
                $t->decimal('discount_total_amount', 26, 8)->nullable();
            });
            $s->create('wc_orders_meta', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id');
                $t->string('meta_key')->nullable();
                $t->text('meta_value')->nullable();
            });
        }
        $this->option('woocommerce_custom_orders_table_enabled', $hpos ? 'yes' : 'no');
    }

    public function option(string $name, mixed $value): void
    {
        $this->db()->table('options')->updateOrInsert(['option_name' => $name], ['option_value' => is_array($value) ? serialize($value) : (string) $value]);
    }

    public function post(int $id, string $type, array $fields = [], array $meta = []): void
    {
        $this->db()->table('posts')->insert(['ID' => $id, 'post_type' => $type, 'post_date' => '2024-05-01 10:00:00', 'post_date_gmt' => '2024-05-01 06:30:00'] + $fields);
        $this->meta('postmeta', 'post_id', $id, $meta);
    }

    public function meta(string $table, string $fk, int $id, array $meta): void
    {
        foreach ($meta as $key => $value) {
            $this->db()->table($table)->insert([$fk => $id, 'meta_key' => $key, 'meta_value' => is_array($value) ? serialize($value) : (string) $value]);
        }
    }

    public function term(int $id, string $taxonomy, string $name, string $slug, int $parent = 0, array $meta = []): void
    {
        $this->db()->table('terms')->insert(['term_id' => $id, 'name' => $name, 'slug' => $slug]);
        $this->db()->table('term_taxonomy')->insert(['term_taxonomy_id' => $id, 'term_id' => $id, 'taxonomy' => $taxonomy, 'parent' => $parent]);
        $this->meta('termmeta', 'term_id', $id, $meta);
    }

    public function relate(int $objectId, int ...$termIds): void
    {
        foreach ($termIds as $i => $termId) {
            $this->db()->table('term_relationships')->insert(['object_id' => $objectId, 'term_taxonomy_id' => $termId, 'term_order' => $i]);
        }
    }

    public function user(int $id, string $login, array $meta, string $email = '', string $role = 'customer'): void
    {
        $this->db()->table('users')->insert(['ID' => $id, 'user_login' => $login, 'user_email' => $email, 'user_registered' => '2023-01-02 08:00:00']);
        $this->meta('usermeta', 'user_id', $id, $meta + ['wp_capabilities' => [$role => true]]);
    }

    public function item(int $orderId, string $type, string $name, array $meta): int
    {
        $id = $this->db()->table('woocommerce_order_items')->insertGetId(['order_id' => $orderId, 'order_item_type' => $type, 'order_item_name' => $name]);
        $this->meta('woocommerce_order_itemmeta', 'order_item_id', $id, $meta);

        return $id;
    }

    public function comment(int $id, int $postId, string $content, array $fields = [], array $meta = []): void
    {
        $this->db()->table('comments')->insert(['comment_ID' => $id, 'comment_post_ID' => $postId, 'comment_content' => $content, 'comment_date_gmt' => '2024-06-01 12:00:00'] + $fields);
        $this->meta('commentmeta', 'comment_id', $id, $meta);
    }

    /** HPOS order with addresses and operational data */
    public function hposOrder(int $id, array $order, array $billing, ?array $shipping = null, array $operational = [], array $meta = []): void
    {
        $this->db()->table('wc_orders')->insert(['id' => $id, 'date_created_gmt' => '2024-07-01 09:00:00'] + $order);
        $this->db()->table('wc_order_addresses')->insert(['order_id' => $id, 'address_type' => 'billing'] + $billing);
        if ($shipping) {
            $this->db()->table('wc_order_addresses')->insert(['order_id' => $id, 'address_type' => 'shipping'] + $shipping);
        }
        $this->db()->table('wc_order_operational_data')->insert(['order_id' => $id] + $operational);
        foreach ($meta as $k => $v) {
            $this->db()->table('wc_orders_meta')->insert(['order_id' => $id, 'meta_key' => $k, 'meta_value' => $v]);
        }
    }
}
