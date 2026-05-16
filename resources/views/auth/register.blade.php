@extends('layouts.app')

@section('content')
    <section class="shell">
        @php
            $redirectTo = old('redirect_to', request('redirect_to'));
            $loginUrl = $redirectTo ? route('login', ['redirect_to' => $redirectTo]) : route('login');
            $accountType = old('account_type', 'customer');
        @endphp

        <div class="mx-auto max-w-2xl market-card p-8">
            <p class="section-kicker">Join</p>
            <h1 class="mt-2 text-4xl font-black">Create Your Account</h1>
            <p class="mt-2 text-slate-500">Register as a customer or apply as a seller.</p>

            <form action="{{ route('register.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 grid gap-4 md:grid-cols-2">
                @csrf
                @if ($redirectTo)
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                @endif

                <input class="field md:col-span-2" type="text" name="name" value="{{ old('name') }}" placeholder="Full name">
                <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Email address">
                <input class="field" type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone number">

                <select class="field" name="account_type" data-account-type-select>
                    <option value="customer" @selected($accountType === 'customer')>Customer</option>
                    <option value="seller" @selected($accountType === 'seller')>Seller</option>
                </select>

                <div class="md:col-span-2 grid gap-4 md:grid-cols-2" data-seller-fields @if ($accountType !== 'seller') hidden @endif>
                    <input class="field" type="text" name="shop_name" value="{{ old('shop_name') }}" placeholder="Shop name">
                    <input class="field" type="text" name="business_address" value="{{ old('business_address') }}" placeholder="Business address">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-800">Profile image</label>
                        <input class="field" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp,image/jpg">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-800">Shop logo</label>
                        <input class="field" type="file" name="shop_logo" accept="image/jpeg,image/png,image/webp,image/jpg">
                    </div>
                    <input class="field" type="text" name="nid_or_trade_license" value="{{ old('nid_or_trade_license') }}" placeholder="Optional NID or trade license">
                    <select class="field" name="account_status">
                        <option value="pending" @selected(old('account_status', 'pending') === 'pending')>Pending approval</option>
                    </select>
                </div>

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

            <div class="mt-6 text-sm">
                <a href="{{ $loginUrl }}" class="font-semibold text-slate-600">Already have an account? Log in</a>
            </div>
        </div>
    </section>
@endsection
