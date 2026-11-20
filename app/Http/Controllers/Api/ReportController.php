<?php

namespace App\Http\Controllers\Api;

use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends ApiController
{
    /** GET /reports/summary?period=today|week|month|all */
    public function summary(Request $request): JsonResponse
    {
        $period = $request->validate(['period' => ['nullable', 'in:today,week,month,all']])['period'] ?? 'month';

        return response()->json(['data' => self::build($request->user(), $period)]);
    }

    /** Shared with the web dashboard. */
    public static function build(User $user, string $period): array
    {
        $from = match ($period) {
            'today' => today(),
            'week' => today()->subDays(6),
            'month' => today()->startOfMonth(),
            default => null,
        };

        $sales = $user->sales()->when($from, fn ($q) => $q->where('sold_at', '>=', $from));
        $revenue = (int) (clone $sales)->sum('total');
        $cost = (int) (clone $sales)->sum('cost_total');
        $expenses = (int) $user->expenses()->when($from, fn ($q) => $q->whereDate('spent_on', '>=', $from))->sum('amount');

        $top = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.user_id', $user->id)
            ->when($from, fn ($q) => $q->where('sales.sold_at', '>=', $from))
            ->selectRaw('sale_items.name, sum(sale_items.qty) as qty, sum(sale_items.total) as revenue')
            ->groupBy('sale_items.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'qty' => (int) $r->qty, 'revenue' => (int) $r->revenue]);

        $daily = collect(range(6, 0))->map(function (int $daysAgo) use ($user) {
            $day = today()->subDays($daysAgo);

            return [
                'date' => $day->toDateString(),
                'revenue' => (int) $user->sales()->whereBetween('sold_at', [$day, $day->copy()->endOfDay()])->sum('total'),
            ];
        });

        return [
            'period' => $period,
            'from' => $from?->toDateString(),
            'sales_count' => (clone $sales)->count(),
            'revenue' => $revenue,
            'cost_of_goods' => $cost,
            'gross_profit' => $revenue - $cost,
            'expenses' => $expenses,
            'net_profit' => $revenue - $cost - $expenses,
            'low_stock' => $user->products()->lowStock()->orderBy('stock')->limit(10)->get(['id', 'name', 'stock', 'low_stock_at']),
            'top_products' => $top,
            'last_7_days' => $daily,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
