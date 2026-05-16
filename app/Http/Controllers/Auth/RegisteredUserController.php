<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $this->captureIntendedRedirect($request);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->captureIntendedRedirect($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'account_type' => ['required', 'in:customer,seller'],
            'shop_name' => ['nullable', 'string', 'max:255', 'required_if:account_type,seller'],
            'business_address' => ['nullable', 'string', 'max:1000', 'required_if:account_type,seller'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'nid_or_trade_license' => ['nullable', 'string', 'max:255'],
            'account_status' => ['nullable', Rule::in(['pending']), 'required_if:account_type,seller'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $profileImagePath = $request->hasFile('profile_image')
            ? $request->file('profile_image')->store('avatars', 'public')
            : null;

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => $validated['account_type'],
            'status' => $validated['account_type'] === 'seller' ? ($validated['account_status'] ?? 'pending') : 'active',
            'avatar' => $profileImagePath,
        ]);

        if ($validated['account_type'] === 'seller') {
            $shopLogoPath = $request->hasFile('shop_logo')
                ? $request->file('shop_logo')->store('seller-logos', 'public')
                : null;

            $profileMeta = array_filter([
                'business_address' => $validated['business_address'] ?? null,
                'nid_or_trade_license' => $validated['nid_or_trade_license'] ?? null,
                'requested_status' => $validated['account_status'] ?? 'pending',
            ], fn ($value) => filled($value));

            $user->sellerProfile()->create([
                'shop_name' => $validated['shop_name'],
                'slug' => $this->uniqueSlug($validated['shop_name'], SellerProfile::class),
                'description' => $validated['business_address'] ?? null,
                'contact_phone' => $validated['phone'],
                'contact_email' => $validated['email'],
                'logo' => $shopLogoPath,
                'payout_details' => $profileMeta,
            ]);
        }

        $user->cart()->firstOrCreate();
        Auth::login($user);
        $request->session()->regenerate();

        $message = $user->isSeller()
            ? 'Seller account created. Your shop is waiting for admin approval.'
            : 'Welcome to the marketplace.';

        $this->logActivity($user, 'user.registered', $message, $user);

        $defaultRoute = $user->isSeller() ? 'seller.dashboard' : 'home';

        return redirect()->intended(route($defaultRoute))->with('success', $message);
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
}
