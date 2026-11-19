<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_money_arithmetic_uses_two_decimal_places(): void
    {
        $this->assertSame('30000.00', Money::multiply('10000.00', 3));
        $this->assertSame('25000.00', Money::add('20000.00', '5000.00'));
        $this->assertSame('5000.00', Money::subtract('25000.00', '20000.00'));
    }
}
