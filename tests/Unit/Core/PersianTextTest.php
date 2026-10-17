<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Unit\Core;

use App\Core\Support\PersianText;
use PHPUnit\Framework\TestCase;

class PersianTextTest extends TestCase
{
    public function test_normalizes_letters_digits_and_spaces(): void
    {
        $this->assertSame('کتری برقی 24 لیتری', PersianText::normalize("  كتري   برقي ۲٤\tليتري "));
    }

    public function test_keeps_zero_width_non_joiner(): void
    {
        $this->assertSame("می\u{200C}خواهم", PersianText::normalize("مي\u{200C}خواهم"));
    }
}
