<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$field => $validated['login'], 'password' => $validated['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->status === 'blocked') {
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => 'Your account is blocked. Please contact support.',
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $user->cart()->firstOrCreate();

        $message = $user->status === 'pending'
            ? 'You are logged in. Some seller features will stay locked until approval.'
            : 'Welcome back.';

        $this->logActivity($user, 'user.logged_in', 'User signed in.', $user);

        $redirectRoute = match (true) {
            $user->isAdmin() => 'admin.dashboard',
            $user->isSeller() => 'seller.dashboard',
            default => 'home',
        };

        return redirect()->intended(route($redirectRoute))->with('success', $message);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logActivity($request->user(), 'user.logged_out', 'User signed out.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}
