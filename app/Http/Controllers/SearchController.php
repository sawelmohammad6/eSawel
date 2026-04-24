<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->with('images')
            ->published()
            ->where('name', 'like', '%'.$query.'%')
            ->limit(6)
            ->get();

        return response()->json(
            $products->map(fn (Product $product) => [
                'name' => $product->name,
                'url' => route('products.show', $product),
                'image' => $this->publicStorageUrl($product->images->first()?->path),
                'price' => number_format($product->effective_price, 2),
            ])
        );
    }

    public function popular(): JsonResponse
    {
        return response()->json(
            SearchLog::query()
                ->orderByDesc('hits')
                ->take(8)
                ->pluck('keyword')
        );
    }
}
