@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')

        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Payout Requests</h1>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="market-card p-5">
                <p class="section-kicker">Pending</p>
                <p class="mt-3 text-3xl font-black">Tk {{ number_format($summary['pending'], 0) }}</p>
            </div>
            <div class="market-card p-5">
                <p class="section-kicker">Approved</p>
                <p class="mt-3 text-3xl font-black">Tk {{ number_format($summary['approved'], 0) }}</p>
            </div>
            <div class="market-card p-5">
                <p class="section-kicker">Paid</p>
                <p class="mt-3 text-3xl font-black">Tk {{ number_format($summary['paid'], 0) }}</p>
            </div>
            <div class="market-card p-5">
                <p class="section-kicker">Rejected</p>
                <p class="mt-3 text-3xl font-black">{{ number_format($summary['rejected']) }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.payouts.index') }}" class="market-card mb-6 p-5">
            <div class="grid gap-3 md:grid-cols-[260px_auto]">
                <select class="field" name="status">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'approved', 'rejected', 'paid'] as $status)
                        <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn-primary" type="submit">Filter</button>
            </div>
        </form>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Account Details</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payoutRequests as $requestItem)
                        @php
                            $statusClass = match ((string) $requestItem->status) {
                                'approved' => 'bg-sky-100 text-sky-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $requestItem->seller?->sellerProfile->shop_name ?? $requestItem->seller?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $requestItem->seller?->email ?? '-' }}</p>
                            </td>
                            <td>Tk {{ number_format((float) $requestItem->amount, 0) }}</td>
                            <td>{{ strtoupper((string) $requestItem->method) }}</td>
                            <td class="max-w-xs whitespace-normal">{{ data_get($requestItem->details, 'account_details', '-') ?: '-' }}</td>
                            <td>
                                <p>{{ optional($requestItem->created_at)->format('d M Y, g:i A') ?? '-' }}</p>
                                @if ($requestItem->processed_at)
                                    <p class="text-xs text-slate-500">Processed {{ optional($requestItem->processed_at)->format('d M Y, g:i A') }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst((string) $requestItem->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if ((string) $requestItem->status === 'pending')
                                        <form action="{{ route('admin.payouts.update', $requestItem) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn-outline" type="submit">Approve</button>
                                        </form>
                                        <form action="{{ route('admin.payouts.update', $requestItem) }}" method="POST" onsubmit="return confirm('Reject this payout request?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn-outline" type="submit">Reject</button>
                                        </form>
                                    @elseif ((string) $requestItem->status === 'approved')
                                        <form action="{{ route('admin.payouts.update', $requestItem) }}" method="POST" onsubmit="return confirm('Mark this payout as paid? This will reduce seller available balance.')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="paid">
                                            <button class="btn-primary" type="submit">Mark as Paid</button>
                                        </form>
                                        <form action="{{ route('admin.payouts.update', $requestItem) }}" method="POST" onsubmit="return confirm('Reject this approved payout request?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn-outline" type="submit">Reject</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-500">No actions</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-5 text-sm text-slate-500">No payout requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $payoutRequests->links() }}</div>
        </div>
    </section>
@endsection
