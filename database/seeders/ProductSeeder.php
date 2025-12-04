<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing products
        Product::truncate();

        $electronicsCategory = \App\Models\Category::where('slug', 'electronics')->first();
        $categoryId = $electronicsCategory ? $electronicsCategory->id : null;

        $products = [
            [
                'name' => 'Premium Wireless Headphones',
                'description' => 'High-quality wireless headphones with noise cancellation and 30-hour battery life.',
                'price' => 199.99,
                'stock' => 50,
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
            [
                'name' => 'Smart Watch Pro',
                'description' => 'Feature-rich smartwatch with health tracking, GPS, and water resistance.',
                'price' => 299.99,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with precision tracking and long battery life.',
                'price' => 49.99,
                'stock' => 100,
                'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB backlit mechanical keyboard with customizable keys and premium switches.',
                'price' => 129.99,
                'stock' => 75,
                'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
            [
                'name' => 'USB-C Hub',
                'description' => 'Multi-port USB-C hub with HDMI, USB 3.0, and SD card reader.',
                'price' => 39.99,
                'stock' => 120,
                'image' => 'https://images.unsplash.com/photo-1625842268584-8f3296236761?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
            [
                'name' => 'Laptop Stand',
                'description' => 'Adjustable aluminum laptop stand for better ergonomics and cooling.',
                'price' => 59.99,
                'stock' => 80,
                'image' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500',
                'category_id' => $categoryId,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

