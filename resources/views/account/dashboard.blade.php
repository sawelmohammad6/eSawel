@extends('layouts.app')

@section('content')
    <section class="shell">
        @php
            $mediaUrl = function (?string $path): string {
                $path = trim((string) $path);

                if ($path === '') {
                    return asset('images/placeholder.svg');
                }

                if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
                    return $path;
                }

                return asset('storage/'.$path);
            };
        @endphp

        <div class="grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-8">
                <div class="market-card p-6">
                    <p class="section-kicker">Account</p>
                    <h1 class="mt-2 text-4xl font-black">Profile</h1>
                    <form action="{{ route('account.profile.update') }}" method="POST" class="mt-6 grid gap-4">
                        @csrf
                        @method('PATCH')
                        <input class="field" type="text" name="name" value="{{ $user->name }}" placeholder="Full name">
                        <input class="field" type="email" name="email" value="{{ $user->email }}" placeholder="Email">
                        <input class="field" type="text" name="phone" value="{{ $user->phone }}" placeholder="Phone">
                        <button class="btn-primary" type="submit">Update Profile</button>
                    </form>
                </div>

                <div class="market-card p-6">
                    <p class="section-kicker">Addresses</p>
                    <h2 class="mt-2 text-3xl font-black">Saved Addresses</h2>

                    <div class="mt-6 space-y-4">
                        @foreach ($user->addresses as $address)
                            <form action="{{ route('account.addresses.update', $address) }}" method="POST" class="rounded-[24px] border border-[#ffd6e5] p-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input class="field" type="text" name="label" value="{{ $address->label }}">
                                    <input class="field" type="text" name="recipient_name" value="{{ $address->recipient_name }}">
                                    <input class="field" type="text" name="phone" value="{{ $address->phone }}">
                                    <input class="field" type="text" name="city" value="{{ $address->city }}">
                                    <input class="field md:col-span-2" type="text" name="address_line_1" value="{{ $address->address_line_1 }}">
                                    <input class="field" type="text" name="address_line_2" value="{{ $address->address_line_2 }}">
                                    <input class="field" type="text" name="state" value="{{ $address->state }}">
                                    <input class="field" type="text" name="postal_code" value="{{ $address->postal_code }}">
                                    <input class="field" type="text" name="country" value="{{ $address->country }}">
                                </div>
                                <div class="mt-3 flex flex-wrap gap-3">
                                    <label class="flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="is_default" value="1" @checked($address->is_default)>
                                        Default address
                                    </label>
                                    <button class="btn-outline" type="submit">Save Address</button>
                                </div>
                            </form>
                        @endforeach
                    </div>

                    <form action="{{ route('account.addresses.store') }}" method="POST" class="mt-6 grid gap-4 rounded-[26px] bg-[var(--color-brand-soft)] p-5 md:grid-cols-2">
                        @csrf
                        <input class="field" type="text" name="label" placeholder="Label">
                        <input class="field" type="text" name="recipient_name" placeholder="Recipient name">
                        <input class="field" type="text" name="phone" placeholder="Phone">
                        <input class="field" type="text" name="city" placeholder="City">
                        <input class="field md:col-span-2" type="text" name="address_line_1" placeholder="Address line 1">
                        <input class="field" type="text" name="address_line_2" placeholder="Address line 2">
                        <input class="field" type="text" name="state" placeholder="State">
                        <input class="field" type="text" name="postal_code" placeholder="Postal code">
                        <input class="field" type="text" name="country" value="Bangladesh" placeholder="Country">
                        <button class="btn-primary md:col-span-2" type="submit">Add New Address</button>
                    </form>
                </div>
            </div>

            <div class="space-y-8">
                <div class="market-card p-6">
                    <p class="section-kicker">Recent Orders</p>
                    <h2 class="mt-2 text-3xl font-black">Latest Activity</h2>
                    <div class="mt-6 space-y-4">
                        @forelse ($recentOrders as $order)
                            <a href="{{ route('orders.show', $order) }}" class="block rounded-[22px] bg-[#fff7fa] p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-black">{{ $order->order_number }}</p>
                                        <p class="text-sm text-slate-500">{{ optional($order->placed_at)->format('d M Y') }}</p>
                                    </div>
                                    <p class="font-black text-[var(--color-brand-rose)]">Tk {{ number_format($order->total_amount, 0) }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="text-slate-500">No orders yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="market-card p-6">
                    <p class="section-kicker">Notifications</p>
                    <h2 class="mt-2 text-3xl font-black">Updates</h2>
                    <div class="mt-6 space-y-3">
                        @forelse ($user->notifications->take(6) as $notification)
                            <div class="rounded-[22px] bg-[#fff7fa] p-4">
                                <p class="font-black">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $notification->data['body'] ?? '' }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500">No notifications yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="market-card p-6">
                    <p class="section-kicker">Recently Viewed</p>
                    <h2 class="mt-2 text-3xl font-black">Continue Browsing</h2>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @forelse ($recentlyViewed as $item)
                            <a href="{{ route('products.show', $item->product) }}" class="rounded-[22px] bg-[#fff7fa] p-3">
                                <img src="{{ $mediaUrl($item->product->images->first()?->path) }}" alt="{{ $item->product->name }}" class="h-36 w-full rounded-[18px] object-cover">
                                <p class="mt-3 font-black">{{ $item->product->name }}</p>
                            </a>
                        @empty
                            <p class="text-slate-500">Recently viewed products will appear here.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
