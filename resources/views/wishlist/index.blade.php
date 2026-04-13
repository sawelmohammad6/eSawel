@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Saved</p>
            <h1 class="section-title">Your Wishlist</h1>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($wishlistItems as $item)
                @include('partials.product-card', ['product' => $item->product])
            @empty
                <div class="market-card col-span-full p-8 text-slate-500">Your wishlist is empty.</div>
            @endforelse
        </div>
    </section>
@endsection
