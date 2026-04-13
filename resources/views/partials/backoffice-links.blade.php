@auth
    @if (auth()->user()->isAdmin() || auth()->user()->isSeller())
        <div class="mb-4 flex flex-wrap gap-3">
            @if (auth()->user()->isSeller() || auth()->user()->isAdmin())
                <a href="{{ route('seller.dashboard') }}" class="rounded-full border border-[#ffd1e3] bg-white px-4 py-2 text-sm font-semibold text-brand-rose shadow-sm">Seller Panel</a>
            @endif
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-[#ffd1e3] bg-white px-4 py-2 text-sm font-semibold text-brand-rose shadow-sm">Admin Panel</a>
                <a href="{{ route('admin.products.index') }}" class="rounded-full border border-[#ffd1e3] bg-white px-4 py-2 text-sm font-semibold text-brand-rose shadow-sm">Admin Products</a>
                <a href="{{ route('admin.categories.index') }}" class="rounded-full border border-[#ffd1e3] bg-white px-4 py-2 text-sm font-semibold text-brand-rose shadow-sm">Admin Categories</a>
            @endif
        </div>
    @endif
@endauth
