<?php

namespace App\Http\Controllers;

use App\Events\ProductUpdatedEvent;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\Ghost\EmptyResource;
use App\Http\Resources\ProductResource;
use App\Jobs\NewProductJob;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $products = $request->filter ? Product::published()->paginate(10) : Product::paginate(10);

            return $this->respondWithSuccess(
                resource: ProductResource::collection($products)->resource,
                message: 'Products retrieved successfully',
            );
        } catch (\Throwable $e) {
            return $this->respondWithError(
                message: $e->getMessage(),
                exception: $e,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request, Product $product): JsonResponse
    {
        $product = $product->create($request->validated());

        if ($request->hasFile('image')) {
            $fileName = $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('products', $fileName);
            $product->update(['image' => $fileName]);
        }

        NewProductJob::dispatch($product);

        return $this->respondWithSuccess(
            resource: ProductResource::make($product),
            message: 'Product created successfully',
            statusCode: 201,
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product): JsonResponse
    {
        return $this->respondWithSuccess(
            resource: ProductResource::make($product),
            message: 'Product retrieved successfully',
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->all());

        event(new ProductUpdatedEvent());

        return $this->respondWithSuccess(
            resource: ProductResource::make($product),
            message: 'Product updated successfully',
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->respondWithSuccess(
            resource: new EmptyResource([]),
            message: 'Product deleted successfully',
        );
    }

    public function download()
    {
        return response()->download(public_path('/temp/babysitting.png'));
    }
}
