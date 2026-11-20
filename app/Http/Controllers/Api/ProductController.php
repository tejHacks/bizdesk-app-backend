<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:60'],
            'low_stock' => ['boolean'],
        ]);

        $products = $request->user()->products()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $product = $request->user()->products()->create($this->validated($request));

        return response()->json(['data' => $product], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        return response()->json(['data' => $this->owned($request, $product)]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->owned($request, $product)->update($this->validated($request, $product, partial: true));

        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(Request $request, Product $product): Response
    {
        $this->owned($request, $product)->delete();   // past sale lines keep their copied name/price

        return response()->noContent();
    }

    /** POST /products/{product}/restock  { "qty": 24 } */
    public function restock(Request $request, Product $product): JsonResponse
    {
        $qty = $request->validate(['qty' => ['required', 'integer', 'min:1', 'max:100000']])['qty'];
        $this->owned($request, $product)->increment('stock', $qty);

        return response()->json(['data' => $product->fresh()]);
    }

    private function validated(Request $request, ?Product $product = null, bool $partial = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40',
                Rule::unique('products')->where('user_id', $request->user()->id)->ignore($product)],
            'category' => ['nullable', 'string', 'max:60'],
            'price' => ['required', 'integer', 'min:1'],             // minor units
            'cost' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_at' => ['nullable', 'integer', 'min:0'],
        ];

        if ($partial) {
            $rules = array_map(fn ($r) => ['sometimes', ...$r], $rules);
        }

        return $request->validate($rules);
    }
}
