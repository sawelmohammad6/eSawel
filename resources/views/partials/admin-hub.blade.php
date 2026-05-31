@php
    $pill = fn (string $pattern) => request()->routeIs($pattern) ? 'admin-hub-pill admin-hub-pill--active' : 'admin-hub-pill';
    $newOrdersCount = $newOrdersCount ?? \App\Models\Order::query()->newForAdmin()->count();
@endphp
<nav class="mb-6 flex flex-wrap gap-3 rounded-[24px] border border-[#ffd1e3] bg-[#fff7fa] p-4" aria-label="Admin panel sections">
    <a href="{{ route('admin.dashboard') }}" class="{{ $pill('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.products.index') }}" class="{{ $pill('admin.products.*') }}">Products</a>
    <a href="{{ route('admin.categories.index') }}" class="{{ $pill('admin.categories.*') }}">Categories</a>
    <a href="{{ route('admin.brands.index') }}" class="{{ $pill('admin.brands.*') }}">Brands</a>
    <a href="{{ route('admin.orders.index') }}" class="{{ $pill('admin.orders.*') }}">
        Orders
        @if ($newOrdersCount > 0)
            <span class="ml-2 rounded-full bg-red-500 px-2 py-0.5 text-xs font-black text-white">{{ $newOrdersCount }} New</span>
        @endif
    </a>
    <a href="{{ route('admin.payouts.index') }}" class="{{ $pill('admin.payouts.*') }}">Payouts</a>
    <a href="{{ route('admin.deliverymen.index') }}" class="{{ $pill('admin.deliverymen.*') }}">Delivery</a>
</nav>
