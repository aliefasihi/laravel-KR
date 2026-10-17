<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Unit\Core;

use App\Core\Support\UrlPath;
use PHPUnit\Framework\TestCase;

class UrlPathTest extends TestCase
{
    public function test_encoded_and_decoded_persian_paths_share_one_key(): void
    {
        $decoded = '/product-category/ظروف-آشپزخانه/';
        $encoded = '/product-category/'.rawurlencode('ظروف-آشپزخانه').'/';

        $this->assertSame('product-category/ظروف-آشپزخانه', UrlPath::normalize($encoded));
        $this->assertSame(UrlPath::hash($decoded), UrlPath::hash($encoded));
    }

    public function test_arabic_letter_variants_match_persian(): void
    {
        $this->assertSame(UrlPath::hash('/كتري-برقي/'), UrlPath::hash('/کتری-برقی'));
    }

    public function test_query_string_and_duplicate_slashes_are_dropped(): void
    {
        $this->assertSame('product/abc', UrlPath::normalize('//Product//abc/?utm=1#x'));
    }

    public function test_full_urls_reduce_to_their_path(): void
    {
        $this->assertSame('product/ظ', UrlPath::normalize('https://khanehromantic.com/product/%D8%B8/?x=1'));
    }

    public function test_wordpress_query_keys_survive(): void
    {
        $this->assertSame('?p=123', UrlPath::normalize('?p=123'));
    }

    public function test_encode_matches_wordpress_lowercase_hex(): void
    {
        $this->assertSame('%d8%b8%d8%b1%d9%88%d9%81/pot', UrlPath::encode('/ظروف/pot/'));
    }
}
