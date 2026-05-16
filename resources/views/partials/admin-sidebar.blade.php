@php
    $linkClass = static fn (string $pattern): string => request()->routeIs($pattern)
        ? 'panel-link panel-link--active'
        : 'panel-link';
@endphp

<aside class="panel-sidebar">
    <p class="section-kicker">Admin Panel</p>
    <h2 class="mt-2 text-2xl font-black text-slate-900">Navigation</h2>

    <nav class="mt-5 space-y-2" aria-label="Admin navigation">
        <a href="{{ route('admin.dashboard') }}" class="{{ $linkClass('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.products.index') }}" class="{{ $linkClass('admin.products.*') }}">Products</a>
        <a href="{{ route('admin.categories.index') }}" class="{{ $linkClass('admin.categories.*') }}">Categories</a>
        <a href="{{ route('admin.brands.index') }}" class="{{ $linkClass('admin.brands.*') }}">Brands</a>
        <a href="{{ route('admin.sellers.index') }}" class="{{ $linkClass('admin.sellers.*') }}">Sellers</a>
        <a href="{{ route('admin.orders.index') }}" class="{{ $linkClass('admin.orders.*') }}">Orders</a>
        <a href="{{ route('admin.payouts.index') }}" class="{{ $linkClass('admin.payouts.*') }}">Payouts</a>
        <a href="{{ route('admin.deliverymen.index') }}" class="{{ $linkClass('admin.deliverymen.*') }}">Delivery</a>
        <a href="{{ route('admin.reports.index') }}" class="{{ $linkClass('admin.reports.*') }}">Reports</a>
        <a href="{{ route('admin.users.index') }}" class="{{ $linkClass('admin.users.*') }}">Users</a>
    </nav>
</aside>
