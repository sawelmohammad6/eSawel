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
    public function create(Request $request): View
    {
        $this->captureIntendedRedirect($request);

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->captureIntendedRedirect($request);

        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'redirect_to' => ['nullable', 'string'],
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
            $user->isDeliveryman() => 'deliveryman.dashboard',
            default => 'home',
        };

        return redirect()->intended(route($redirectRoute))->with('success', $message);
    }

    protected function captureIntendedRedirect(Request $request): void
    {
        $redirectTo = $this->validatedRedirectTarget($request->input('redirect_to', $request->query('redirect_to')));

        if ($redirectTo !== null) {
            $request->session()->put('url.intended', $redirectTo);
        }
    }

    protected function validatedRedirectTarget(mixed $value): ?string
    {
        $target = trim((string) $value);

        if ($target === '') {
            return null;
        }

        if (str_starts_with($target, '/')) {
            return $target;
        }

        $parsed = parse_url($target);

        if (! is_array($parsed)) {
            return null;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = strtolower((string) ($parsed['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $requestHost = strtolower((string) request()->getHost());
        $allowedHosts = array_filter([$appHost, $requestHost]);

        if (! in_array($host, $allowedHosts, true)) {
            return null;
        }

        $path = (string) ($parsed['path'] ?? '/');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        return $path.$query;
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
