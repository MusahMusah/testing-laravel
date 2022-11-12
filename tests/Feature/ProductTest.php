<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    public function test_product_screen_can_be_rendered()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
    }

    public function test_fetch_all_products_list(): void
    {
        $products  = Product::factory(10)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'result' => [
                'data' => $products->toArray(),
            ],
        ]);
        $this->assertCount(10, $response->json()['result']['data']);
    }

    public function test_fetch_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson(route('products.show', $product->id));

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'result' => $product->toArray(),
            ]);
    }


    public function test_create_product_successful(): void
    {
        $product = [
            'name' => 'Product 1',
            'price' => 100,
        ];

        $response = $this->postJson(route('products.store'), $product);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'result' => $product
            ]);
    }

    public function testCreateProductValidationError(): void
    {
        $product = [
            'name' => 'Product 1',
        ];

        $response = $this->postJson(route('products.store'), $product);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'errors' => true
            ]);
    }
}
