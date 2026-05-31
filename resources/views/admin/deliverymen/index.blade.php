@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')

        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Delivery Management</h1>
        </div>

        <div class="market-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black">Delivery Charge Settings</h2>
                </div>
                <div class="rounded-[22px] bg-[var(--color-brand-soft)] px-4 py-3 text-sm font-semibold text-slate-700">
                    Standard Tk {{ number_format((float) $deliveryChargeOptions['standard'], 0) }} | Express Tk {{ number_format((float) $deliveryChargeOptions['express'], 0) }}
                </div>
            </div>

            <form action="{{ route('admin.delivery-settings.update') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Standard Delivery Charge</span>
                    <input
                        class="field"
                        type="number"
                        name="standard_delivery_charge"
                        min="0"
                        step="1"
                        value="{{ old('standard_delivery_charge', number_format((float) $deliveryChargeSettings['standard_delivery_charge'], 0, '.', '')) }}"
                        required
                    >
                    @error('standard_delivery_charge')
                        <span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Express Delivery Charge</span>
                    <input
                        class="field"
                        type="number"
                        name="express_delivery_charge"
                        min="0"
                        step="1"
                        value="{{ old('express_delivery_charge', number_format((float) $deliveryChargeSettings['express_delivery_charge'], 0, '.', '')) }}"
                        required
                    >
                    @error('express_delivery_charge')
                        <span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <button class="btn-primary" type="submit">Save Charges</button>
            </form>
        </div>

        <div class="market-card p-6">
            <h2 class="text-2xl font-black">Create Deliveryman</h2>
            <form action="{{ route('admin.deliverymen.store') }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @csrf
                <input class="field" type="text" name="name" placeholder="Full name" required>
                <input class="field" type="email" name="email" placeholder="Email" required>
                <input class="field" type="text" name="phone" placeholder="Phone" required>
                <input class="field" type="password" name="password" placeholder="Password" required>
                <select class="field" name="status" required>
                    @foreach (['active', 'pending', 'blocked'] as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn-primary" type="submit">Create Account</button>
            </form>
        </div>

        <div class="mt-8">
            <div class="mb-4">
                <h2 class="text-2xl font-black">Deliveryman Accounts</h2>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Update</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliverymen as $deliveryman)
                            <tr>
                                <td>{{ $deliveryman->name }}</td>
                                <td>
                                    <div class="text-sm text-slate-600">
                                        <p>{{ $deliveryman->phone ?: '-' }}</p>
                                        <p>{{ $deliveryman->email ?: '-' }}</p>
                                    </div>
                                </td>
                                <td>{{ ucfirst($deliveryman->status) }}</td>
                                <td>
                                    <form action="{{ route('admin.deliverymen.update', $deliveryman) }}" method="POST" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                        @csrf
                                        @method('PUT')
                                        <input class="field sm:col-span-2" type="text" name="name" value="{{ $deliveryman->name }}" required>
                                        <input class="field" type="email" name="email" value="{{ $deliveryman->email }}">
                                        <input class="field" type="text" name="phone" value="{{ $deliveryman->phone }}" required>
                                        <select class="field" name="status" required>
                                            @foreach (['active', 'pending', 'blocked'] as $status)
                                                <option value="{{ $status }}" @selected($deliveryman->status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <input class="field sm:col-span-2" type="password" name="password" placeholder="New password (optional)">
                                        <button class="btn-outline sm:col-span-2 lg:col-span-1" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('admin.deliverymen.destroy', $deliveryman) }}" method="POST" onsubmit="return confirm('Delete this deliveryman account?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-outline" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-5 text-sm text-slate-500">No deliveryman accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $deliverymen->links() }}</div>
            </div>
        </div>

        <div class="mt-8">
            <div class="mb-4">
                <h2 class="text-2xl font-black">Assigned Deliveries</h2>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Seller</th>
                            <th>Deliveryman</th>
                            <th>Delivery Status</th>
                            <th>COD Collection</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignedDeliveries as $item)
                            <tr>
                                <td>{{ $item->order?->order_number ?? '-' }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->order?->user?->name ?? '-' }}</td>
                                <td>{{ $item->seller?->name ?? '-' }}</td>
                                <td>{{ $item->deliveryman?->name ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', (string) $item->delivery_status)) }}</td>
                                <td>
                                    @if ($item->order?->payment_method === 'cod')
                                        {{ $item->payment_collected_at ? 'Collected' : 'Pending' }}
                                    @else
                                        Prepaid
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-sm text-slate-500">No assigned deliveries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $assignedDeliveries->links() }}</div>
            </div>
        </div>

        <div class="mt-8">
            <div class="mb-4">
                <h2 class="text-2xl font-black">Completed Deliveries</h2>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Deliveryman</th>
                            <th>Customer</th>
                            <th>Delivered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($completedDeliveries as $item)
                            <tr>
                                <td>{{ $item->order?->order_number ?? '-' }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->deliveryman?->name ?? '-' }}</td>
                                <td>{{ $item->order?->user?->name ?? '-' }}</td>
                                <td>{{ optional($item->delivered_at)->format('d M Y, g:i A') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-5 text-sm text-slate-500">No completed deliveries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
