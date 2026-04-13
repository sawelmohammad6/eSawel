<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Varsity Market' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $navCategories = \App\Models\Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->take(10)->get();
    $wishlistCount = auth()->check() ? auth()->user()->wishlistItems()->count() : 0;
    $cartCount = auth()->check() ? auth()->user()->cart?->items()->count() ?? 0 : 0;
    $compareCount = count(session('compare_products', []));
@endphp
<body>
    <div class="fixed inset-0 z-50 hidden" data-category-drawer>
        <button class="absolute inset-0 bg-slate-950/45 backdrop-blur-sm" data-drawer-close></button>
        <aside class="absolute left-0 top-0 flex h-full w-full max-w-sm flex-col gap-6 bg-white px-6 py-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="section-kicker">Explore</p>
                    <h2 class="text-3xl font-black text-brand-rose">Categories</h2>
                </div>
                <button class="rounded-full border border-[#ffd1e3] px-3 py-2 text-sm font-semibold text-brand-rose" data-drawer-close>Close</button>
            </div>

            <div class="space-y-3">
                @forelse ($navCategories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="flex items-center justify-between rounded-2xl border border-[#ffe0eb] px-4 py-3 font-semibold text-slate-700 transition hover:border-brand-rose hover:bg-brand-soft">
                        <span>{{ $category->name }}</span>
                        <span class="text-brand-rose">+</span>
                    </a>
                @empty
                    <p class="rounded-2xl bg-brand-soft px-4 py-3 text-sm text-slate-500">Categories will appear here after you add them from the admin panel.</p>
                @endforelse
            </div>

            <div class="mt-auto space-y-3 rounded-[28px] bg-brand-soft p-4">
                <p class="text-sm font-semibold text-slate-500">Support</p>
                <a href="mailto:support@example.com" class="block rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm">support@example.com</a>
                <a href="tel:+8801900000000" class="block rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm">+880 1900 000000</a>
            </div>
        </aside>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/20 bg-[linear-gradient(90deg,#c1005f,#e91572,#f33384)] text-white shadow-[0_18px_45px_rgba(157,0,73,0.25)]">
        <div class="shell flex flex-wrap items-center gap-4 py-4">
            <div class="flex items-center gap-3">
                <button class="rounded-full border border-white/30 px-3 py-2 text-lg font-bold" data-drawer-open>&#9776;</button>
                <a href="{{ route('home') }}" class="text-3xl font-black tracking-tight">eSawel</a>
            </div>

            <div class="min-w-[260px] flex-1" data-search-box>
                <form action="{{ route('products.index') }}" method="GET" class="relative">
                    <input class="input-shell pr-28" type="search" name="q" value="{{ request('q') }}" placeholder="Search products..." data-search-input>
                    <button class="btn-primary absolute right-2 top-1/2 -translate-y-1/2 px-6 py-2" type="submit">Search</button>
                    <div class="absolute left-0 right-0 top-[calc(100%+0.5rem)] hidden overflow-hidden rounded-[24px] border border-[#ffd6e5] bg-white shadow-2xl" data-search-results></div>
                </form>
            </div>

            <nav class="ml-auto flex items-center gap-3 text-sm font-semibold">
                <a href="{{ route('compare.index') }}" class="topbar-link">Compare <span class="rounded-full bg-white/15 px-2 py-1 text-xs">{{ $compareCount }}</span></a>
                @auth
                    <a href="{{ route('account.dashboard') }}" class="topbar-link">Profile</a>
                    <a href="{{ route('wishlist.index') }}" class="topbar-link">Wishlist <span class="rounded-full bg-white/15 px-2 py-1 text-xs">{{ $wishlistCount }}</span></a>
                    <a href="{{ route('cart.index') }}" class="topbar-link">Cart <span class="rounded-full bg-white/15 px-2 py-1 text-xs">{{ $cartCount }}</span></a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="topbar-link">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="topbar-link">Log In</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-white px-4 py-2 text-brand-rose">Sign Up</a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="shell py-4">
        @include('partials.backoffice-links')
        @include('partials.flash')
    </div>

    <main class="pb-16">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-10">
        <div class="shell grid gap-8 md:grid-cols-3">
            <div>
                <p class="text-2xl font-black text-brand-rose">eSawel</p>
                <p class="mt-2 text-sm text-slate-600">Trusted online marketplace for quality products, fast support, and secure shopping.</p>
            </div>
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-slate-500">Contact</p>
                <div class="mt-3 space-y-2 text-sm text-slate-700">
                    <p>Address: 123 Main Road, Dhaka 1207, Bangladesh</p>
                    <p>Phone: +880 1900 000000</p>
                    <p>Email: support@esawel.com</p>
                </div>
            </div>
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-slate-500">Follow us</p>
                <div class="mt-3 flex items-center gap-3">
                    <a href="#" aria-label="Facebook" class="rounded-full border border-slate-200 p-2 text-slate-600 transition hover:border-brand-rose hover:text-brand-rose">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13.5 22v-8h2.6l.4-3h-3V9.2c0-.9.3-1.6 1.6-1.6h1.6V5c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.2V11H8v3h2.8v8h2.7z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="Instagram" class="rounded-full border border-slate-200 p-2 text-slate-600 transition hover:border-brand-rose hover:text-brand-rose">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9zm9.75 1.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="WhatsApp" class="rounded-full border border-slate-200 p-2 text-slate-600 transition hover:border-brand-rose hover:text-brand-rose">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.5 3.5A11.4 11.4 0 0 0 12.4.1C6.2.1 1.1 5.2 1.1 11.4c0 2 .5 3.9 1.5 5.6L1 23l6.1-1.6c1.6.9 3.4 1.3 5.3 1.3h.1c6.2 0 11.3-5.1 11.3-11.3 0-3-1.2-5.8-3.3-7.9zm-8 17.2h-.1c-1.7 0-3.4-.5-4.8-1.4l-.3-.2-3.6.9 1-3.5-.2-.4A9.3 9.3 0 0 1 3 11.4c0-5.1 4.2-9.3 9.3-9.3 2.5 0 4.8 1 6.6 2.7a9.2 9.2 0 0 1 2.7 6.6c0 5.1-4.1 9.3-9.2 9.3zm5.1-6.9c-.3-.1-1.8-.9-2-.9-.3-.1-.4-.1-.6.1l-.9 1.1c-.2.2-.3.2-.6.1-1.5-.7-2.5-1.2-3.5-2.8-.3-.4.3-.4.8-1.4.1-.2.1-.4 0-.5l-.9-2.1c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.4s-.9.9-.9 2.2 1 2.4 1.1 2.5c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.8 2.1-1.6.3-.8.3-1.4.2-1.6-.1 0-.3-.1-.6-.3z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="YouTube" class="rounded-full border border-slate-200 p-2 text-slate-600 transition hover:border-brand-rose hover:text-brand-rose">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23 12s0-3.6-.5-5.3c-.3-1-1.1-1.8-2.1-2.1C18.7 4 12 4 12 4s-6.7 0-8.4.6c-1 .3-1.8 1.1-2.1 2.1C1 8.4 1 12 1 12s0 3.6.5 5.3c.3 1 1.1 1.8 2.1 2.1 1.7.6 8.4.6 8.4.6s6.7 0 8.4-.6c1-.3 1.8-1.1 2.1-2.1.5-1.7.5-5.3.5-5.3zM10 15.5v-7l6 3.5-6 3.5z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
