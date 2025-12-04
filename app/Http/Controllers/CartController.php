<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    /**
     * Display the cart page
     */
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $price = (float) $product->price;
                $itemTotal = $price * $quantity;
                $total += $itemTotal;
                $cartItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $price,
                    'quantity' => (int) $quantity,
                    'subtotal' => $itemTotal,
                    'image' => $product->image_url, // Use accessor to handle URLs correctly
                ];
            }
        }

        return Inertia::render('Cart', [
            'items' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Add product to cart
     */
    public function add(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        $quantity = $request->input('quantity', 1);

        if (isset($cart[$product->id])) {
            $cart[$product->id] += $quantity;
        } else {
            $cart[$product->id] = $quantity;
        }

        $request->session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Product added to cart');
    }

    /**
     * Remove product from cart
     */
    public function remove(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$product->id])) {
            unset($cart[$product->id]);
            $request->session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Product removed from cart');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        $quantity = $request->input('quantity', 1);

        if ($quantity > 0) {
            $cart[$product->id] = $quantity;
        } else {
            unset($cart[$product->id]);
        }

        $request->session()->put('cart', $cart);

        return redirect()->back();
    }

    /**
     * Clear the entire cart
     */
    public function clear(Request $request)
    {
        $request->session()->forget('cart');

        return redirect()->route('cart.index')->with('success', 'Cart cleared');
    }

    /**
     * Get cart data for API/drawer
     */
    public function getCartData(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $price = (float) $product->price;
                $itemTotal = $price * $quantity;
                $total += $itemTotal;
                $cartItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $price,
                    'quantity' => (int) $quantity,
                    'subtotal' => $itemTotal,
                    'image' => $product->image_url, // Use accessor to handle URLs correctly
                ];
            }
        }

        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'count' => array_sum($cart),
        ]);
    }
}

