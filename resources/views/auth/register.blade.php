@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mx-auto max-w-2xl market-card p-8">
            <p class="section-kicker">Join</p>
            <h1 class="mt-2 text-4xl font-black">Create Your Account</h1>
            <p class="mt-2 text-slate-500">Register as a customer or apply as a seller.</p>

            <form action="{{ route('register.store') }}" method="POST" class="mt-8 grid gap-4 md:grid-cols-2">
                @csrf
                <input class="field md:col-span-2" type="text" name="name" value="{{ old('name') }}" placeholder="Full name">
                <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Email address">
                <input class="field" type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone number">
                <select class="field" name="account_type">
                    <option value="customer">Customer</option>
                    <option value="seller" @selected(old('account_type') === 'seller')>Seller</option>
                </select>
                <input class="field" type="text" name="shop_name" value="{{ old('shop_name') }}" placeholder="Shop name for sellers">
                <div class="relative" data-password-wrapper>
                    <input class="field pr-20" type="password" name="password" placeholder="Password" autocomplete="new-password" data-password-input>
                    <button
                        class="absolute inset-y-0 right-0 flex items-center px-4 text-xs font-semibold text-slate-500 transition hover:text-slate-700"
                        type="button"
                        aria-label="Show password"
                        aria-pressed="false"
                        data-password-toggle
                    >
                        Show
                    </button>
                </div>
                <div class="relative" data-password-wrapper>
                    <input class="field pr-20" type="password" name="password_confirmation" placeholder="Confirm password" autocomplete="new-password" data-password-input>
                    <button
                        class="absolute inset-y-0 right-0 flex items-center px-4 text-xs font-semibold text-slate-500 transition hover:text-slate-700"
                        type="button"
                        aria-label="Show password"
                        aria-pressed="false"
                        data-password-toggle
                    >
                        Show
                    </button>
                </div>
                <button class="btn-primary md:col-span-2" type="submit">Create Account</button>
            </form>
        </div>
    </section>
@endsection
