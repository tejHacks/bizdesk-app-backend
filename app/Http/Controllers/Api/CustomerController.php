<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $customers = $request->user()->customers()
            ->withSum('sales as total_spent', 'total')
            ->withCount('sales')
            ->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(50);

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user()->customers()->create($this->validated($request));

        return response()->json(['data' => $customer], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->owned($request, $customer);

        return response()->json([
            'data' => $customer,
            'total_spent' => (int) $customer->sales()->sum('total'),
            'recent_sales' => $customer->sales()->latest('sold_at')->limit(10)->get(['id', 'number', 'total', 'sold_at']),
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->owned($request, $customer)->update($this->validated($request, partial: true));

        return response()->json(['data' => $customer->fresh()]);
    }

    public function destroy(Request $request, Customer $customer): Response
    {
        $this->owned($request, $customer)->delete();   // their sales become "walk-in"

        return response()->noContent();
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        return $request->validate($partial ? array_map(fn ($r) => ['sometimes', ...$r], $rules) : $rules);
    }
}
