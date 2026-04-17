@php
    $pill = fn (string $pattern) => request()->routeIs($pattern) ? 'admin-hub-pill admin-hub-pill--active' : 'admin-hub-pill';
@endphp
<nav class="mb-6 flex flex-wrap gap-3 rounded-[24px] border border-[#ffd1e3] bg-[#fff7fa] p-4" aria-label="Admin panel sections">
    <a href="{{ route('admin.dashboard') }}" class="{{ $pill('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.products.index') }}" class="{{ $pill('admin.products.*') }}">Products</a>
    <a href="{{ route('admin.categories.index') }}" class="{{ $pill('admin.categories.*') }}">Categories</a>
    <a href="{{ route('admin.brands.index') }}" class="{{ $pill('admin.brands.*') }}">Brands</a>
</nav>
