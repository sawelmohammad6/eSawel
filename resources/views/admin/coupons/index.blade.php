@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Discounts</p>
                <h1 class="mt-2 text-3xl font-black">Coupons</h1>
                <form action="{{ route('admin.coupons.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input class="field" type="text" name="name" placeholder="Coupon name">
                    <input class="field" type="text" name="code" placeholder="Code">
                    <select class="field" name="type">
                        <option value="fixed">Fixed</option>
                        <option value="percentage">Percentage</option>
                    </select>
                    <input class="field" type="number" step="0.01" name="value" placeholder="Value">
                    <input class="field" type="number" step="0.01" name="min_order_amount" placeholder="Minimum order amount">
                    <input class="field" type="number" step="0.01" name="max_discount_amount" placeholder="Maximum discount">
                    <input class="field" type="number" name="usage_limit" placeholder="Usage limit">
                    <input class="field" type="number" name="per_user_limit" value="1" placeholder="Per user limit">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn-primary w-full" type="submit">Create Coupon</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <td>{{ $coupon->code }}</td>
                                <td>{{ ucfirst($coupon->type) }}</td>
                                <td>
                                    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="name" value="{{ $coupon->name }}">
                                        <input class="field" type="text" name="code" value="{{ $coupon->code }}">
                                        <select class="field" name="type">
                                            <option value="fixed" @selected($coupon->type === 'fixed')>Fixed</option>
                                            <option value="percentage" @selected($coupon->type === 'percentage')>Percentage</option>
                                        </select>
                                        <input class="field" type="number" step="0.01" name="value" value="{{ $coupon->value }}">
                                        <input class="field" type="number" step="0.01" name="min_order_amount" value="{{ $coupon->min_order_amount }}">
                                        <input class="field" type="number" step="0.01" name="max_discount_amount" value="{{ $coupon->max_discount_amount }}">
                                        <label class="flex items-center gap-2 text-sm text-slate-600 md:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($coupon->is_active)> Active</label>
                                        <button class="btn-outline md:col-span-2" type="submit">Save Coupon</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
