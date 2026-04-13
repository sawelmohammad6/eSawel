@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Seller Management</h1>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Shop</th>
                        <th>Status</th>
                        <th>Commission</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sellers as $seller)
                        <tr>
                            <td>{{ $seller->name }}<br><span class="text-xs text-slate-400">{{ $seller->email }}</span></td>
                            <td>{{ $seller->sellerProfile->shop_name ?? '-' }}</td>
                            <td>{{ ucfirst($seller->status) }}</td>
                            <td>{{ number_format($seller->sellerProfile->commission_rate ?? 0, 2) }}%</td>
                            <td>
                                @if ($seller->status !== 'active')
                                    <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                                        @csrf
                                        <button class="btn-primary" type="submit">Approve</button>
                                    </form>
                                @else
                                    <span class="font-semibold text-emerald-600">Approved</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $sellers->links() }}</div>
        </div>
    </section>
@endsection
