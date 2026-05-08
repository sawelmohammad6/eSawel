<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $categoryTree = [
            'Men Fashion' => [
                'Men Footwear',
                'Men Bottomwear',
                'Men Topwear',
                'Men Fashion Accessories',
            ],
            'Women Fashion' => [
                'Women Bottom Wear',
                'Women Fashion Accessories',
                'Women Footwear',
            ],
            'Kids' => [
                'Kids Accessories',
                'Kids Boys Clothing',
                'Kids Girls Clothing',
                'Kids Footwear',
            ],
            'Baby' => [
                'Baby Care',
                'Baby Clothing Set',
                'Baby Footwear',
            ],
            'Health & Beauty' => [
                'Haircare',
                'Makeup',
                'Wellness & Hygiene',
            ],
            'Other Accessories' => [],
        ];

        $parentSortOrder = 0;

        foreach ($categoryTree as $parentName => $subcategories) {
            $parentSortOrder++;

            $parentCategory = Category::query()->firstOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'parent_id' => null,
                    'description' => null,
                    'image' => null,
                    'is_featured' => false,
                    'is_active' => true,
                    'sort_order' => $parentSortOrder,
                ]
            );

            $parentCategory->update([
                'name' => $parentName,
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => $parentSortOrder,
            ]);

            $subcategorySortOrder = 0;

            foreach ($subcategories as $subcategoryName) {
                $subcategorySortOrder++;

                $subcategory = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($subcategoryName)],
                    [
                        'name' => $subcategoryName,
                        'parent_id' => $parentCategory->id,
                        'description' => null,
                        'image' => null,
                        'is_featured' => false,
                        'is_active' => true,
                        'sort_order' => $subcategorySortOrder,
                    ]
                );

                $subcategory->update([
                    'name' => $subcategoryName,
                    'parent_id' => $parentCategory->id,
                    'is_active' => true,
                    'sort_order' => $subcategorySortOrder,
                ]);
            }
        }
    }
}
