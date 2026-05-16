@php
    $linkClass = static fn (string $pattern): string => request()->routeIs($pattern)
        ? 'panel-link panel-link--active'
        : 'panel-link';
@endphp

<aside class="panel-sidebar">
    <p class="section-kicker">Seller Panel</p>
    <h2 class="mt-2 text-2xl font-black text-slate-900">Navigation</h2>

    <nav class="mt-5 space-y-2" aria-label="Seller navigation">
        <a href="{{ route('seller.dashboard') }}" class="{{ $linkClass('seller.dashboard') }}">Dashboard</a>
        <a href="{{ route('seller.products.index') }}" class="{{ $linkClass('seller.products.*') }}">Products</a>
        <a href="{{ route('seller.orders.index') }}" class="{{ $linkClass('seller.orders.*') }}">Orders</a>
        <a href="{{ route('seller.payouts.index') }}" class="{{ $linkClass('seller.payouts.*') }}">Payouts</a>
    </nav>
</aside>
