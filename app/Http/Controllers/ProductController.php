<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\Ghost\EmptyResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        try {
            $products = Product::paginate(10);

            return $this->respondWithSuccess(
                resource: ProductResource::collection($products)->resource,
                message: 'Products retrieved successfully',
            );
        } catch (\Throwable $e) {
            return $this->respondWithError(
                message: $e->getMessage(),
                statusCode: $e->getStatusCode(),
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
}
