@php
    $money = fn (int $minor) => $user->currency.number_format($minor / 100, 2);
    $max = max(1, collect($report['last_7_days'])->max('revenue'));
@endphp
<x-layout title="Dashboard">
    <div class="row" style="justify-content: space-between; margin-bottom: 12px">
        <div>
            <p class="eyebrow">{{ now()->format('l, j F Y') }}</p>
            <h1 style="margin:0">{{ $user->business_name }}</h1>
        </div>
        <div class="chips">
            @foreach (['today' => 'Today', 'week' => '7 days', 'month' => 'This month', 'all' => 'All time'] as $value => $label)
                <a href="{{ route('dashboard', ['period' => $value]) }}" @class(['chip', 'active' => $period === $value])>{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="stats">
        <div class="stat"><small>Revenue</small><strong>{{ $money($report['revenue']) }}</strong></div>
        <div class="stat"><small>Sales</small><strong>{{ $report['sales_count'] }}</strong></div>
        <div class="stat"><small>Gross profit</small><strong>{{ $money($report['gross_profit']) }}</strong></div>
        <div class="stat"><small>Expenses</small><strong>{{ $money($report['expenses']) }}</strong></div>
        <div class="stat"><small>Net profit</small>
            <strong style="color: {{ $report['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' }}">{{ $money($report['net_profit']) }}</strong></div>
    </div>

    <div class="grid-2">
        <section class="panel">
            <h3 style="margin-top:0">Last 7 days</h3>
            <div class="bars">
                @foreach ($report['last_7_days'] as $day)
                    <div title="{{ $day['date'] }}: {{ $money($day['revenue']) }}">
                        <span style="height: {{ round($day['revenue'] / $max * 100) }}%"></span>
                        <small>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D') }}</small>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <h3 style="margin-top:0">Low stock</h3>
            @forelse ($report['low_stock'] as $p)
                <div class="row" style="justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border)">
                    <span>{{ $p->name }}</span>
                    <span class="badge {{ $p->stock === 0 ? 'badge-advanced' : 'badge-intermediate' }}">{{ $p->stock }} left</span>
                </div>
            @empty
                <p class="muted">Everything is well stocked. 👍</p>
            @endforelse
        </section>

        <section class="panel">
            <h3 style="margin-top:0">Best sellers</h3>
            <table class="list">
                <tr><th>Product</th><th class="num">Qty</th><th class="num">Revenue</th></tr>
                @forelse ($report['top_products'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td class="num">{{ $p['qty'] }}</td><td class="num">{{ $money($p['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">No sales in this period.</td></tr>
                @endforelse
            </table>
        </section>

        <section class="panel">
            <h3 style="margin-top:0">Recent sales</h3>
            <table class="list">
                @forelse ($recent as $sale)
                    <tr>
                        <td>{{ $sale->receipt_no }}<br><small class="muted">{{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->sold_at->diffForHumans() }}</small></td>
                        <td class="num">{{ $money($sale->total) }}<br><small class="muted">{{ ucfirst($sale->payment_method) }}</small></td>
                    </tr>
                @empty
                    <tr><td class="muted">No sales yet. Record one from the BizDesk app.</td></tr>
                @endforelse
            </table>
        </section>
    </div>

    <p class="muted" style="margin-top:18px">{{ $counts['products'] }} products · {{ $counts['customers'] }} customers · API base URL: <code>{{ url('/api/v1') }}</code></p>
</x-layout>
