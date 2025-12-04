<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the home page with featured products
     */
    public function index()
    {
        $featuredProducts = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'image' => $product->image_url, // Uses the accessor which handles URLs correctly
                    'stock' => (int) $product->stock,
                ];
            });

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                ];
            });

        return Inertia::render('Home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }
}

