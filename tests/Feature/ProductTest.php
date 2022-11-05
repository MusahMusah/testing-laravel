<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    public function test_homepage_contains_empty_table()
    {
        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSeeText('No products found');
    }

    public function test_homepage_contains_non_empty_table()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 100,
        ]);

        // Act
        $response = $this->get('/products');

        // Assert
        $response->assertOk();
        $response->assertDontSee('No products found');
        $response->assertSeeText($product->name);
        $response->assertViewHas('products', function ($products) use ($product) {
            return $products->contains($product);
        });
    }
}
