<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Marketplace Admin',
                'phone' => '01700000001',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $seller = User::query()->updateOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Friday Fashion',
                'phone' => '01700000002',
                'password' => 'password',
                'role' => 'seller',
                'status' => 'active',
            ]
        );

        $seller->sellerProfile()->updateOrCreate(
            ['user_id' => $seller->id],
            [
                'shop_name' => 'Shop Friday',
                'slug' => 'shop-friday',
                'contact_phone' => $seller->phone,
                'contact_email' => $seller->email,
                'commission_rate' => 10,
                'is_approved' => true,
                'approved_at' => now(),
                'total_earnings' => 125000,
                'total_paid' => 40000,
            ]
        );

        $pendingSeller = User::query()->updateOrCreate(
            ['email' => 'pending-seller@example.com'],
            [
                'name' => 'Pending Seller',
                'phone' => '01700000003',
                'password' => 'password',
                'role' => 'seller',
                'status' => 'pending',
            ]
        );

        $pendingSeller->sellerProfile()->updateOrCreate(
            ['user_id' => $pendingSeller->id],
            [
                'shop_name' => 'Glow Valley',
                'slug' => 'glow-valley',
                'contact_phone' => $pendingSeller->phone,
                'contact_email' => $pendingSeller->email,
                'commission_rate' => 12,
                'is_approved' => false,
            ]
        );

        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Demo Customer',
                'phone' => '01700000004',
                'password' => 'password',
                'role' => 'customer',
                'status' => 'active',
            ]
        );

        $categories = collect([
            'Women Fashion',
            'Men Topwear',
            'Kids Clothing',
            'Baby Care',
            'Health & Beauty',
            'Accessories',
        ])->mapWithKeys(function (string $name): array {
            $slug = Str::slug($name);

            return [
                $slug => Category::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => "{$name} collection",
                        'image' => "https://picsum.photos/seed/{$slug}/400/400",
                        'is_featured' => true,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]
                ),
            ];
        });

        $brands = collect([
            'ZUQQ',
            'WishCare',
            'Diagram',
            'Bosphorus',
        ])->mapWithKeys(function (string $name): array {
            $slug = Str::slug($name);

            return [
                $slug => Brand::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => "{$name} brand",
                        'logo' => "https://picsum.photos/seed/brand-{$slug}/280/180",
                        'is_featured' => true,
                        'is_active' => true,
                    ]
                ),
            ];
        });

        Banner::query()->updateOrCreate(
            ['title' => 'Exclusive Women Collection'],
            [
                'subtitle' => 'Imported styles and local favorites',
                'image' => 'https://picsum.photos/seed/banner-women/1200/700',
                'link' => '/products?category=women-fashion',
                'placement' => 'home_hero',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Banner::query()->updateOrCreate(
            ['title' => 'Exclusive Men Collection'],
            [
                'subtitle' => 'Trending everyday essentials',
                'image' => 'https://picsum.photos/seed/banner-men/1200/700',
                'link' => '/products?category=men-topwear',
                'placement' => 'promo',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Welcome Discount',
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 1000,
                'per_user_limit' => 1,
                'is_active' => true,
            ]
        );

        $productDefinitions = [
            ['sku' => 'VF-1001', 'name' => 'Imported Cream Floral Layered Baby Dress', 'category' => 'baby-care', 'brand' => 'wishcare', 'base_price' => 1750, 'sale_price' => 1520],
            ['sku' => 'WM-1002', 'name' => 'Elegant Women Flat Sandals', 'category' => 'women-fashion', 'brand' => 'zuqq', 'base_price' => 2500, 'sale_price' => 1999],
            ['sku' => 'MN-1003', 'name' => 'Classic Men Polo Shirt', 'category' => 'men-topwear', 'brand' => 'diagram', 'base_price' => 1850, 'sale_price' => 1499],
            ['sku' => 'KD-1004', 'name' => 'Fashionable Kids Girls Clothing Set', 'category' => 'kids-clothing', 'brand' => 'wishcare', 'base_price' => 2100, 'sale_price' => 1690],
            ['sku' => 'AC-1005', 'name' => 'Premium Silver Dog Tag Pendant', 'category' => 'accessories', 'brand' => 'bosphorus', 'base_price' => 1300, 'sale_price' => 990],
            ['sku' => 'HB-1006', 'name' => 'Authentic Wellness Skin Care Combo', 'category' => 'health-beauty', 'brand' => 'wishcare', 'base_price' => 999, 'sale_price' => 850],
        ];

        $products = collect($productDefinitions)->map(function (array $definition) use ($seller, $categories, $brands) {
            $slug = Str::slug($definition['name']);

            $product = Product::query()->updateOrCreate(
                ['sku' => $definition['sku']],
                [
                    'seller_id' => $seller->id,
                    'category_id' => $categories[$definition['category']]->id,
                    'brand_id' => $brands[$definition['brand']]->id,
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'short_description' => $definition['name'].' demo description.',
                    'description' => $definition['name'].' is seeded as demo data so the storefront looks complete immediately after migration.',
                    'specifications' => ['Premium quality', 'Responsive product card', 'Admin editable'],
                    'attributes' => ['Small', 'Medium', 'Large'],
                    'base_price' => $definition['base_price'],
                    'sale_price' => $definition['sale_price'],
                    'stock_quantity' => 30,
                    'low_stock_threshold' => 5,
                    'is_featured' => true,
                    'is_trending' => true,
                    'is_flash_deal' => true,
                    'status' => 'published',
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]
            );

            $product->images()->delete();
            $product->images()->createMany([
                ['path' => "https://picsum.photos/seed/{$slug}-1/900/900", 'alt_text' => $product->name, 'is_primary' => true, 'sort_order' => 0],
                ['path' => "https://picsum.photos/seed/{$slug}-2/900/900", 'alt_text' => $product->name, 'is_primary' => false, 'sort_order' => 1],
            ]);

            return $product;
        });

        $customer->cart()->firstOrCreate();
        $customer->cart->items()->updateOrCreate(
            ['product_id' => $products[0]->id],
            ['quantity' => 1, 'unit_price' => $products[0]->effective_price]
        );
        $customer->cart->items()->updateOrCreate(
            ['product_id' => $products[1]->id],
            ['quantity' => 2, 'unit_price' => $products[1]->effective_price]
        );

        $address = Address::query()->updateOrCreate(
            ['user_id' => $customer->id, 'label' => 'Home'],
            [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address_line_1' => 'Dhanmondi 27',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'is_default' => true,
            ]
        );

        $order = Order::query()->updateOrCreate(
            ['order_number' => 'ORD-DEMO-0001'],
            [
                'user_id' => $customer->id,
                'shipping_address' => [
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line_1' => $address->address_line_1,
                    'city' => $address->city,
                    'country' => $address->country,
                ],
                'delivery_method' => 'standard',
                'tracking_number' => 'TRK-DEMO-001',
                'status' => 'completed',
                'delivery_status' => 'delivered',
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'subtotal' => 5509,
                'discount_amount' => 500,
                'shipping_amount' => 60,
                'total_amount' => 5069,
                'placed_at' => now()->subDays(3),
                'delivered_at' => now()->subDay(),
            ]
        );

        OrderItem::query()->updateOrCreate(
            ['order_id' => $order->id, 'sku' => $products[0]->sku],
            [
                'product_id' => $products[0]->id,
                'seller_id' => $seller->id,
                'product_name' => $products[0]->name,
                'quantity' => 1,
                'unit_price' => $products[0]->effective_price,
                'total_price' => $products[0]->effective_price,
                'status' => 'delivered',
            ]
        );

        OrderItem::query()->updateOrCreate(
            ['order_id' => $order->id, 'sku' => $products[1]->sku],
            [
                'product_id' => $products[1]->id,
                'seller_id' => $seller->id,
                'product_name' => $products[1]->name,
                'quantity' => 2,
                'unit_price' => $products[1]->effective_price,
                'total_price' => $products[1]->effective_price * 2,
                'status' => 'delivered',
            ]
        );

        Payment::query()->updateOrCreate(
            ['transaction_id' => 'TXN-DEMO-0001'],
            [
                'order_id' => $order->id,
                'user_id' => $customer->id,
                'method' => 'cod',
                'provider' => 'cash',
                'amount' => $order->total_amount,
                'currency' => 'BDT',
                'status' => 'paid',
                'paid_at' => now()->subDays(2),
            ]
        );

        Review::query()->updateOrCreate(
            ['user_id' => $customer->id, 'product_id' => $products[0]->id],
            [
                'rating' => 5,
                'title' => 'Beautiful finish',
                'content' => 'The layered dress looks premium and the product page layout feels very close to the reference design.',
                'is_approved' => true,
            ]
        );

        SearchLog::query()->updateOrCreate(['keyword' => 'kids clothing'], ['user_id' => $customer->id, 'hits' => 12]);
        SearchLog::query()->updateOrCreate(['keyword' => 'men shirt'], ['user_id' => $customer->id, 'hits' => 8]);
    }
}
