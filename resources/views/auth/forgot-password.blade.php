@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mx-auto max-w-xl market-card p-8">
            <p class="section-kicker">Recovery</p>
            <h1 class="mt-2 text-4xl font-black">Forgot Password</h1>
            <form action="{{ route('password.email') }}" method="POST" class="mt-8 space-y-4">
                @csrf
                <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Email address">
                <button class="btn-primary w-full" type="submit">Send Reset Link</button>
            </form>
        </div>
    </section>
@endsection
