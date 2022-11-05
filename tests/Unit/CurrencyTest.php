<?php

namespace Tests\Unit;

use App\Service\CurrencyService;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_convert_usd_to_eur_successful() {
        $this->assertEquals(862.4, CurrencyService::convertCurrency(880, 'usd', 'eur'));
    }

    public function test_convert_usd_to_ngn_returns_zero()
    {
        $this->assertEquals(0, CurrencyService::convertCurrency(880, 'usd', 'ngn'));
    }

    public function test_convert_usd_to_usd_returns_same_amount()
    {
        $this->assertEquals(880, CurrencyService::convertCurrency(880, 'usd', 'usd'));
    }
}
