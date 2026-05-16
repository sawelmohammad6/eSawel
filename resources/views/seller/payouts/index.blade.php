@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Payouts</p>
                <h1 class="mt-2 text-3xl font-black">Request Withdrawal</h1>
                <p class="mt-2 text-slate-500">Available balance: Tk {{ number_format($availableBalance, 0) }}</p>

                <form action="{{ route('seller.payouts.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input class="field" type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" placeholder="Amount">
                    <select class="field" name="method">
                        <option value="bank" @selected(old('method') === 'bank')>Bank</option>
                        <option value="bkash" @selected(old('method') === 'bkash')>bKash</option>
                        <option value="nagad" @selected(old('method') === 'nagad')>Nagad</option>
                    </select>
                    <textarea class="field min-h-28" name="account_details" placeholder="Account details">{{ old('account_details') }}</textarea>
                    <button class="btn-primary w-full" type="submit">Request Payout</button>
                </form>
            </div>

            <div class="table-shell" data-payout-auto-refresh data-payout-refresh-interval="20000">
                <div class="border-b border-[#ffe1ec] px-4 py-3 text-xs font-semibold text-slate-500">
                    Status updates refresh automatically every 20 seconds.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Account Details</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payouts as $payout)
                            @php
                                $statusClass = match ((string) $payout->status) {
                                    'approved' => 'bg-sky-100 text-sky-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td>Tk {{ number_format((float) $payout->amount, 0) }}</td>
                                <td>{{ strtoupper((string) $payout->method) }}</td>
                                <td class="max-w-xs whitespace-normal">{{ data_get($payout->details, 'account_details', '-') ?: '-' }}</td>
                                <td>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst((string) $payout->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($payout->created_at)->format('d M Y, g:i A') ?? '-' }}</td>
                                <td>{{ optional($payout->processed_at)->format('d M Y, g:i A') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-sm text-slate-500">No payout requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $payouts->links() }}</div>
            </div>
        </div>
    </section>
@endsection
