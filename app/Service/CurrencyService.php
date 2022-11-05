<?php

namespace App\Service;

class CurrencyService
{
    const RATES = [
        'usd' => [
            'eur' => 0.98,
        ]
    ];


    public static function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if (!isset(self::RATES[$fromCurrency][$toCurrency])) {
            throw new \Exception('Currency conversion not supported');
        }

        $rate = self::RATES[$fromCurrency][$toCurrency] ?? 0;

        return round($amount * $rate, 2);
    }

    public function convert($amount, $from, $to)
    {
        $rates = $this->getRates();
        $fromRate = $rates[$from];
        $toRate = $rates[$to];
        return $amount * $toRate / $fromRate;
    }

    private function getRates()
    {
        return [
            'USD' => 1,
            'EUR' => 0.8,
            'GBP' => 0.7,
        ];
    }
}
