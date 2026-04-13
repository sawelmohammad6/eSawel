@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mx-auto max-w-xl market-card p-8">
            <p class="section-kicker">Account</p>
            <h1 class="mt-2 text-4xl font-black">Log In</h1>
            <p class="mt-2 text-slate-500">Use your email or phone to sign in.</p>

            <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-4">
                @csrf
                <input class="field" type="text" name="login" value="{{ old('login') }}" placeholder="Email or phone">
                <input class="field" type="password" name="password" placeholder="Password">
                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <button class="btn-primary w-full" type="submit">Log In</button>
            </form>

            <div class="mt-6 flex items-center justify-between text-sm">
                <a href="{{ route('password.request') }}" class="font-semibold text-[var(--color-brand-rose)]">Forgot password?</a>
                <a href="{{ route('register') }}" class="font-semibold text-slate-600">Create account</a>
            </div>
        </div>
    </section>
@endsection
