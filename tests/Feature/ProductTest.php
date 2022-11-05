<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
    }

    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->actingAs($this->user)->get('/products');

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

    public function test_paginated_products_doesnt_contain_11th_record()
    {
        // Arrange
        $products = Product::factory(11)->create();
        $lastProduct = $products->last();

        // Act
        $response = $this->get('/products');

        // Assert
        $response->assertOk();
        $response->assertDontSeeText($lastProduct->name);
        $response->assertViewHas('products', function ($products) use ($lastProduct) {
            return !$products->contains($lastProduct);
        });
    }

    public function test_unauthenticated_user_cannot_access_product()
    {
        $response = $this->get('/products');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }
}
