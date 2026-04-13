@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mx-auto max-w-xl market-card p-8">
            <p class="section-kicker">Recovery</p>
            <h1 class="mt-2 text-4xl font-black">Reset Password</h1>
            <form action="{{ route('password.store') }}" method="POST" class="mt-8 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <input class="field" type="email" name="email" value="{{ old('email', $request->email) }}" placeholder="Email address">
                <input class="field" type="password" name="password" placeholder="New password">
                <input class="field" type="password" name="password_confirmation" placeholder="Confirm password">
                <button class="btn-primary w-full" type="submit">Reset Password</button>
            </form>
        </div>
    </section>
@endsection
