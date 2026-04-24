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

        <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
            <form action="{{ route('checkout.store') }}" method="POST" class="market-card p-6 lg:p-8">
                @csrf
                <p class="section-kicker">Checkout</p>
                <h1 class="mt-2 text-4xl font-black">Delivery & Payment</h1>

                <div class="mt-8 space-y-8">
                    <div>
                        <h2 class="text-2xl font-black">Choose Address</h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($addresses as $address)
                                <label class="block rounded-[24px] border border-[#ffd6e5] p-4">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="address_id" value="{{ $address->id }}" @checked($loop->first)>
                                        <div>
                                            <p class="font-black">{{ $address->label }} - {{ $address->recipient_name }}</p>
                                            <p class="text-sm text-slate-500">{{ $address->address_line_1 }}, {{ $address->city }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h2 class="text-2xl font-black">Or Add New Address</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <input class="field" type="text" name="recipient_name" placeholder="Recipient name">
                            <input class="field" type="text" name="phone" placeholder="Phone number">
                            <input class="field md:col-span-2" type="text" name="address_line_1" placeholder="Address line 1">
                            <input class="field" type="text" name="address_line_2" placeholder="Address line 2">
                            <input class="field" type="text" name="city" placeholder="City">
                            <input class="field" type="text" name="state" placeholder="State">
                            <input class="field" type="text" name="postal_code" placeholder="Postal code">
                            <input class="field" type="text" name="country" value="Bangladesh" placeholder="Country">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <h2 class="mb-3 text-2xl font-black">Delivery Method</h2>
                            <select class="field" name="delivery_method">
                                <option value="standard">Standard</option>
                                <option value="express">Express</option>
                            </select>
                        </div>
                        <div>
                            <h2 class="mb-3 text-2xl font-black">Payment Method</h2>
                            <select class="field" name="payment_method">
                                <option value="cod">Cash on Delivery</option>
                                <option value="stripe">Stripe Demo</option>
                                <option value="bkash">bKash Demo</option>
                                <option value="sslcommerz">SSLCommerz Demo</option>
                            </select>
                        </div>
                    </div>

                    <input class="field" type="text" name="coupon_code" placeholder="Coupon code">
                    <textarea class="field min-h-28" name="notes" placeholder="Order notes"></textarea>

                    <button class="btn-primary" type="submit">Place Order</button>
                </div>
            </form>

            <aside class="market-card h-fit p-6">
                <h2 class="text-2xl font-black">Order Summary</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($cart->items as $item)
                        <div class="flex items-center gap-3">
                            <img src="{{ $mediaUrl($item->product->images->first()?->path) }}" alt="{{ $item->product->name }}" class="h-16 w-16 rounded-[18px] object-cover">
                            <div class="flex-1">
                                <p class="font-semibold text-slate-800">{{ $item->product->name }}</p>
                                <p class="text-sm text-slate-500">Qty {{ $item->quantity }}</p>
                            </div>
                            <p class="font-black text-[var(--color-brand-rose)]">Tk {{ number_format($item->total, 0) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 space-y-3 border-t border-[#ffe1ec] pt-4 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span>Tk {{ number_format($cart->subtotal, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Shipping</span>
                        <span>Calculated at order time</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
