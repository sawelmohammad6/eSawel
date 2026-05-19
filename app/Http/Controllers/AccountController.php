<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\RecentlyViewedProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user()->load(['addresses', 'notifications']);
        $recentOrders = $user->orders()
            ->with(['orderItems.product'])
            ->latest()
            ->take(5)
            ->get();
        $pointTransactions = $user->pointTransactions()
            ->latest()
            ->take(12)
            ->get();
        $earnedReturnPoints = (int) $user->pointTransactions()
            ->whereIn('type', ['return_credit', 'return_refund'])
            ->where('points', '>', 0)
            ->sum('points');
        $usedPoints = abs((int) $user->pointTransactions()
            ->where('points', '<', 0)
            ->sum('points'));
        $recentlyViewed = $user->isCustomer()
            ? RecentlyViewedProduct::query()
                ->where('user_id', $user->id)
                ->with('product.images')
                ->latest('viewed_at')
                ->take(6)
                ->get()
            : collect();

        return view('account.dashboard', compact(
            'user',
            'recentOrders',
            'pointTransactions',
            'earnedReturnPoints',
            'usedPoints',
            'recentlyViewed'
        ));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone,'.$request->user()->id],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $request->user()->addresses()->create([
            ...$validated,
            'country' => $validated['country'] ?? 'Bangladesh',
            'is_default' => $request->boolean('is_default') || $request->user()->addresses()->doesntExist(),
        ]);

        return back()->with('success', 'Address added successfully.');
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update([
            ...$validated,
            'country' => $validated['country'] ?? 'Bangladesh',
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Address updated successfully.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $address->delete();

        return back()->with('success', 'Address removed successfully.');
    }
}
