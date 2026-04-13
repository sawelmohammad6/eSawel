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
                    <input class="field" type="number" step="0.01" name="amount" placeholder="Amount">
                    <select class="field" name="method">
                        <option value="bank">Bank</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                    </select>
                    <textarea class="field min-h-28" name="account_details" placeholder="Account details"></textarea>
                    <button class="btn-primary w-full" type="submit">Request Payout</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr>
                                <td>Tk {{ number_format($payout->amount, 0) }}</td>
                                <td>{{ strtoupper($payout->method) }}</td>
                                <td>{{ ucfirst($payout->status) }}</td>
                                <td>{{ $payout->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $payouts->links() }}</div>
            </div>
        </div>
    </section>
@endsection
