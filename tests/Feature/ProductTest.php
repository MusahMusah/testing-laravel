<?php

namespace Tests\Feature;

use App\Events\ProductUpdatedEvent;
use App\Jobs\NewProductJob;
use App\Jobs\PublishProductJob;
use App\Mail\NewProductCreatedMail;
use App\Models\User;
use App\Notifications\NewProductCreatedNotification;
use App\Services\ProductService;
use App\Models\Product;
use Brick\Math\Exception\NumberFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TODO: php artisan test --stop-on-failure
     * TODO: $this->markTestSkipped('skipped for now');
     */
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
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();
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

        Mail::assertSent(NewProductCreatedMail::class);
        Notification::assertSentTo($user, NewProductCreatedNotification::class);
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
                'data' => [] //$product->toArray()
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

    public function test_product_show_when_published_at_correct_time(): void
    {
        $product = Product::factory()->create([
            'published_at' => now()->addDay()->setTime(14, 00)
        ]);

        $response = $this->getJson(route('products.index', ['filter' => 'published']));

        $this->freezeTime(function () use ($product) {
            $this->travelTo(now()->addDay()->setTime(14, 10));
            $response = $this->getJson(route('products.index', ['filter' => 'published']));
            $response
                ->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'data' => [
                            0 => [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->price
                            ]
                        ]
                    ]
                ]);
        });

        $response
            ->assertOk()
            ->assertJsonMissing($product->toArray())
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    public function testCreateProductImageUpload(): void
    {
        $this->markTestSkipped('skipped for now');
        Storage::fake();
        $fileName = 'photo1.png';
        $productData = [
            'name' => 'Product 123',
            'price' => 120,
            'image' => UploadedFile::fake()->image($fileName),
        ];

        $response = $this->postJson(route('products.store'), $productData);

        $response
            ->assertCreated()
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
            ]);

        $lastProduct = Product::query()->latest()->first();

        $this->assertEquals($fileName, $lastProduct->image);

        Storage::assertExists("products/${fileName}");
    }

    public function testCreateProductJobDispatchedSuccessfully(): void
    {
        Bus::fake();

        $productData = [
            'name' => 'Product 1',
            'price' => 200,
        ];

        $response = $this->postJson(route('products.store'), $productData);
        $response
            ->assertCreated()
            ->assertSuccessful();

        Bus::assertDispatched(NewProductJob::class);
    }

    public function testCreateProductMailAndNotificationSent(): void
    {
        Mail::fake();
        Notification::fake();

//        $this->expectsNotification()
//        $this->expectsJobs();

        $user = User::factory()->create();

        $product = [
            'name' => 'Product 1',
            'price' => 300,
        ];

        $response = $this->postJson(route('products.store'), $product);
        $response
            ->assertCreated()
            ->assertSuccessful();

        Mail::assertSent(NewProductCreatedMail::class);
        Notification::assertSentTo($user, NewProductCreatedNotification::class);
    }

    public function testUpdateProductFiresEvent(): void
    {
        Event::fake();
        $this->expectsEvents(ProductUpdatedEvent::class);

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
            ->assertSuccessful();

        Event::assertDispatched(ProductUpdatedEvent::class);
    }
}
