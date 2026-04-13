<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'account_type' => ['required', 'in:customer,seller'],
            'shop_name' => ['nullable', 'string', 'max:255', 'required_if:account_type,seller'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => $validated['account_type'],
            'status' => $validated['account_type'] === 'seller' ? 'pending' : 'active',
        ]);

        if ($validated['account_type'] === 'seller') {
            $user->sellerProfile()->create([
                'shop_name' => $validated['shop_name'],
                'slug' => $this->uniqueSlug($validated['shop_name'], SellerProfile::class),
                'contact_phone' => $validated['phone'],
                'contact_email' => $validated['email'],
            ]);
        }

        $user->cart()->firstOrCreate();
        Auth::login($user);
        $request->session()->regenerate();

        $message = $user->isSeller()
            ? 'Seller account created. Your shop is waiting for admin approval.'
            : 'Welcome to the marketplace.';

        $this->logActivity($user, 'user.registered', $message, $user);

        return redirect()->route('home')->with('success', $message);
    }
}
