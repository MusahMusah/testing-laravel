<?php

namespace App\Models;

use App\Service\CurrencyService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
    ];

    // laravel 9 accessor and mutator
    public function priceEur(): Attribute
    {
        return Attribute::make(
            get : fn ($value) => CurrencyService::convertCurrency($this->price, 'usd', 'eur'),
        );
    }
}
