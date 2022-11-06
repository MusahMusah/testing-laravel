<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());
        return redirect()->route('products.index');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index');
    }
}
