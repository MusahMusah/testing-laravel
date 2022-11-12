<?php

beforeEach(function () {
    $this->user = createUser();
    $this->admin = createUser(isAdmin: true);
});

// update product
it('should update product', function () {
    $product = createProduct();

    $this->actingAs($this->admin)
        ->putJson(route('products.update', $product->id), [
            'name' => 'Product 1',
            'price' => 100,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'result' => [
                'name' => 'Product 1',
                'price' => 100,
            ],
        ]);
});

// update product return validation error
test('should return validation error', function () {
    $product = createProduct();

    $this->actingAs($this->admin)
        ->putJson(route('products.update', $product->id), [
            'name' => 'Product 1',
        ])
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'errors' => true,
        ]);
});

// delete product
it('should delete product', function () {
    $product = createProduct();

    $this->actingAs($this->user)
        ->deleteJson(route('products.destroy', $product->id))
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

