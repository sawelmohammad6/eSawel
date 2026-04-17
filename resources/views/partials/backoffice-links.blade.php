@auth
    @if (auth()->user()->isAdmin())
        <div class="mb-4 flex flex-wrap gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-[#ffd1e3] bg-white px-4 py-2 text-sm font-semibold text-brand-rose shadow-sm">Admin Panel</a>
        </div>
    @endif
@endauth
