<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Product::where('store_id', $request->user()->store_id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create([
            'store_id' => $request->user()->store_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product successfully created.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product)
    {
        $this->authorizeProduct($product, $request);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorizeProduct($product, $request);

        $product->update($request->only(['name', 'sku', 'price', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product successfully updated.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product)
    {
        $this->authorizeProduct($product, $request);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product successfully deleted.',
        ]);
    }

    /**
     * Ensure admin only manages products in their store
     */
    protected function authorizeProduct(Product $product, Request $request)
    {
        if ($product->store_id !== $request->user()->store_id) {
            abort(403, 'You are not authorized to manage this product.');
        }
    }
}
