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
        $response->assertViewIs('products.index');
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

    public function test_create_product_validation_error_redirects_back_to_form(): void
    {
        $response = $this->actingAs($this->admin)->post('/products', [
            'name' => '',
            'price' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'price']);
        $response->assertInvalid(['name', 'price']);
    }

    public function test_create_product_successful()
    {
        $product = [
            'name' => 'Test Product',
            'price' => 100,
        ];

        $response = $this->actingAs($this->admin)->post('/products', $product);

        $response->assertStatus(302);
        $response->assertRedirect('/products');
        $this->assertDatabaseHas('products', $product);
        $lastProduct = Product::latest()->first();
        $this->assertEquals($product['name'], $lastProduct->name);
        $this->assertEquals($product['price'], $lastProduct->price);
    }

    public function test_create_product_unsuccessful()
    {
        $product = [
            'name' => 'Test Product',
            'price' => 100,
        ];

        $response = $this->actingAs($this->user)->post('/products', $product);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', $product);
    }

    public function test_product_edit_contains_correct_values(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->admin)->get("/products/{$product->id}/edit");

        // Assert
        $response->assertOk();
        $response->assertSee("value=\"{$product->name}\"", false);
        $response->assertSee("value=\"{$product->price}\"", false);
        $response->assertViewHas('product', $product);
    }

    public function test_update_product_validation_error_redirects_back_to_form()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->put("/products/{$product->id}", [
            'name' => '',
            'price' => '212',
        ]);

        $response->assertStatus(302);
        $response->assertInvalid(['name']);
    }

    public function test_update_product_successful(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->put("/products/{$product->id}", [
            'name' => 'Test Updated',
            'price' => 100,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/products');
    }

    public function test_product_delete_successful(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/products/{$product->id}");

        $response->assertStatus(302);
        $response->assertRedirect('/products');
        $this->assertDatabaseMissing('products', $product->toArray());
        $this->assertDatabaseCount('products', 0);
    }
}
