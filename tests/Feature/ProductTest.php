<?php

namespace Tests\Feature;

use App\Jobs\PublishProductJob;
use App\Services\ProductService;
use App\Models\Product;
use Brick\Math\Exception\NumberFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    public function test_product_screen_can_be_rendered()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertTrue(true);
    }

    public function test_fetch_all_products_list(): void
    {
        $products  = Product::factory(10)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'data' => $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                    ];
                })->toArray(),
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $products[0]->id,
            'name' => $products[0]->name,
            'price' => $products[0]->price
        ]);
        $this->assertCount(10, $response->json()['data']['data']);
    }

    public function test_fetch_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson(route('products.show', $product->id));

        $response
            ->assertOk()
            ->assertSuccessful()
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'price',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => $product->all(['id', 'name', 'price'])->first()->toArray(),
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $product->name,
            'price' => $product->getAttributes()['price'],
        ]);
        $this->assertModelExists($product);
    }


    public function test_create_product_successful(): void
    {
        $product = [
            'name' => 'Product 1',
            'price' => 100,
        ];

        $response = $this->postJson(route('products.store'), $product);
        $lastProduct = Product::latest()->first();

        $response
            ->assertCreated()
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => $product
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Product 1',
            'price' => 10000
        ]);
        $this->assertEquals($product['name'], $lastProduct->name);
        $this->assertEquals($product['price'], $lastProduct->price);
    }

    public function testCreateProductValidationError(): void
    {
        $product = [
            'name' => 'Product 1',
        ];

        $response = $this->postJson(route('products.store'), $product);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'errors' => true
            ]);
    }

    public function testProductServiceCreateReturnsProduct(): void
    {
        $data = [
            'name' => 'Product New',
            'price' => 1234,
        ];

        $product = (new ProductService())->create(name: $data['name'], price: $data['price']);

        $this->assertInstanceOf(Product::class, $product);
    }

    public function testProductServiceCreateReturnsException(): void
    {
        try {
            $data = [
                'name' => 'Product New',
                'price' => 1234567,
            ];

            (new ProductService())->create(name: $data['name'], price: $data['price']);
        } catch (NumberFormatException $exception) {
            $this->assertInstanceOf(NumberFormatException::class, $exception);
        }
    }

    public function testUpdateProduct(): void
    {
        $data = [
            'name' => 'Musah Cloth',
            'price' => 100,
        ];
        $product = Product::create($data);

        $response = $this->patchJson(route('products.update', $product->id), [
            'name' => 'Musah Cloth Updated',
            'price' => 200,
        ]);

        $response
            ->assertOk()
            ->assertSuccessful()
            ->assertJsonMissing($data)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    public function testUpdateProductValidationError(): void
    {
        $data = [
            'name' => 'Musah Cloth',
            'price' => 100,
        ];
        $product = Product::create($data);

        $response = $this->patchJson(route('products.update', $product->id), [
            'name' => '',
            'price' => 200,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonMissingValidationErrors(['price'])
            ->assertInvalid('name')
            ->assertJson([
                'success' => false,
                'errors' => true,
            ]);
    }

    public function testDeleteProduct(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson(route('products.destroy', $product->id));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => null
            ]);
        $this->assertDatabaseMissing('products', $product->toArray());
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('products', 0);
    }

    public function testFileDownload(): void
    {
        $response = $this->getJson(route('download'));

        $response
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=babysitting.png');
    }

    public function testPublishProductJobSuccess()
    {
        $product = Product::factory()->create();

        $this->assertNull($product->published_at);
        (new PublishProductJob($product))->handle();
        $product->refresh();
        $this->assertNotNull($product->published_at);
    }
}
