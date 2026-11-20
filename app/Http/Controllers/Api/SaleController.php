<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordSale;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'customer_id' => ['nullable', 'integer'],
        ]);

        $sales = $request->user()->sales()
            ->with(['items', 'customer:id,name'])
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('sold_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('sold_at', '<=', $d))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->latest('sold_at')
            ->paginate(25)
            ->withQueryString();

        return response()->json($sales);
    }

    /**
     * POST /sales
     * { "items": [{"product_id": 1, "qty": 2}], "customer_id": 3, "discount": 500, "payment_method": "cash" }
     */
    public function store(Request $request, RecordSale $recordSale): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('user_id', $userId)],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('user_id', $userId)],
            'discount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', Rule::in(Sale::PAYMENT_METHODS)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $sale = $recordSale->handle(
            user: $request->user(),
            lines: $data['items'],
            customerId: $data['customer_id'] ?? null,
            discount: $data['discount'] ?? 0,
            paymentMethod: $data['payment_method'] ?? 'cash',
            note: $data['note'] ?? null,
        );

        return response()->json(['data' => $this->present($sale)], 201);
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $this->owned($request, $sale);

        return response()->json(['data' => $this->present($sale->load('items', 'customer'))]);
    }

    /** DELETE /sales/{sale}: void the sale and put its items back in stock. */
    public function destroy(Request $request, Sale $sale): Response
    {
        $this->owned($request, $sale);

        DB::transaction(function () use ($request, $sale) {
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $request->user()->products()->whereKey($item->product_id)->increment('stock', $item->qty);
                }
            }
            $sale->delete();
        });

        return response()->noContent();
    }

    private function present(Sale $sale): array
    {
        return [
            ...$sale->toArray(),
            'receipt_no' => $sale->receipt_no,
            'profit' => $sale->profit,
        ];
    }
}
