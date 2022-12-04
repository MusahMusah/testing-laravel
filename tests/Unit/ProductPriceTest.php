<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function testProductPriceWillBeConvertedToCents(): void
    {
        $data = [
            'name' => 'Shoe',
            'price' => 100,
        ];

        $product = new Product($data);

        $this->assertEquals(10000, $product->getAttributes()['price']);
    }

    public function testProductPriceWillRevertedToDollars(): void
    {
        $data = [
            'name' => 'Shoe',
            'price' => 100,
        ];

        $product = new Product($data);

        $this->assertEquals(100, $product->price);
    }
}
