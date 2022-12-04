<?php

namespace Tests\Unit;

use App\Exceptions\CurrencyRateNotFoundException;
use App\Services\CurrencyService;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function testTheSameFromCurrencyAndToCurrencyReturnsSameAmountPassed(): void
    {
        $this->assertEquals(10, (new CurrencyService())::convertCurrency(amount: 10, fromCurrency: 'usd', toCurrency: 'usd'));
    }

    public function testUnsupportedCurrencyRateThrowsException(): void
    {
        $this->expectException(CurrencyRateNotFoundException::class);

        $this->assertEquals(10, (new CurrencyService())::convertCurrency(amount: 10, fromCurrency: 'usd', toCurrency: 'ngn'));
    }

    public function testConvertUSDToEUR(): void
    {
        $this->assertEquals(19.6, (new CurrencyService())::convertCurrency(amount: 20, fromCurrency: 'usd', toCurrency: 'eur'));
    }
}
