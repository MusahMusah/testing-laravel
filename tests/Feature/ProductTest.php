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
    protected User $user, $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->admin = $this->createUser(isAdmin: true);
    }

    private function createUser(bool $isAdmin = false): User
    {
        return User::factory()->create([
            'is_admin' => $isAdmin,
        ]);
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    public function test_homepage_contains_empty_table(): void
    {
        $response = $this->actingAs($this->user)->get('/products');

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
        $response = $this->actingAs($this->user)->get('/products');

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
        $response = $this->actingAs($this->user)->get('/products');

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

    public function test_admin_can_see_products_create_button()
    {
        $response = $this->actingAs($this->admin)->get('/products');

        $response->assertOk();
        $response->assertSeeText('Add new product');
    }

    public function test_non_admin_cannot_see_products_create_button()
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertDontSeeText('Add new product');
    }

    public function test_admin_can_access_product_create_page()
    {
        $response = $this->actingAs($this->admin)->get('/products/create');

        $response->assertOk();
    }

    public function test_non_admin_can_access_product_create_page()
    {
        $response = $this->actingAs($this->user)->get('/products/create');

        $response->assertStatus(403);
    }
}
