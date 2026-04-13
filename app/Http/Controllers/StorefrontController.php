<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchLog;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $heroBanners = Banner::query()
            ->where('is_active', true)
            ->where('placement', 'home_hero')
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        $promoBanners = Banner::query()
            ->where('is_active', true)
            ->where('placement', 'promo')
            ->orderBy('sort_order')
            ->take(2)
            ->get();

        $featuredCategories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(16)
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->take(8)
            ->get();

        $featuredProducts = Product::query()
            ->with(['images', 'brand', 'category'])
            ->published()
            ->where('is_featured', true)
            ->latest()
            ->take(10)
            ->get();

        $trendingProducts = Product::query()
            ->with(['images', 'brand', 'category'])
            ->published()
            ->where('is_trending', true)
            ->latest()
            ->take(10)
            ->get();

        $flashProducts = Product::query()
            ->with(['images', 'brand', 'category'])
            ->published()
            ->where('is_flash_deal', true)
            ->latest()
            ->take(10)
            ->get();

        $popularSearches = SearchLog::query()
            ->orderByDesc('hits')
            ->take(8)
            ->get();

        return view('home', compact(
            'heroBanners',
            'promoBanners',
            'featuredCategories',
            'brands',
            'featuredProducts',
            'trendingProducts',
            'flashProducts',
            'popularSearches',
        ));
    }
}
