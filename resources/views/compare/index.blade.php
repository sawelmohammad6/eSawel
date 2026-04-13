@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Compare</p>
            <h1 class="section-title">Product Comparison</h1>
        </div>

        @if ($products->isEmpty())
            <div class="market-card p-8 text-slate-500">Add up to four products to compare features, pricing, and ratings.</div>
        @else
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            @foreach ($products as $product)
                                <th>{{ $product->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Price</td>
                            @foreach ($products as $product)
                                <td>Tk {{ number_format($product->effective_price, 0) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>Brand</td>
                            @foreach ($products as $product)
                                <td>{{ $product->brand?->name ?? 'Marketplace' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>Category</td>
                            @foreach ($products as $product)
                                <td>{{ $product->category?->name }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>Rating</td>
                            @foreach ($products as $product)
                                <td>{{ number_format($product->average_rating, 1) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
