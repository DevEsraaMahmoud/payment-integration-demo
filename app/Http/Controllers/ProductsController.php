<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    /**
     * Display listing of products
     */
    public function index()
    {
        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price, // Ensure price is a float
                    'stock' => (int) $product->stock, // Ensure stock is an integer
                    'image' => $product->image,
                ];
            });

        return Inertia::render('ProductsIndex', [
            'products' => $products,
        ]);
    }
}

