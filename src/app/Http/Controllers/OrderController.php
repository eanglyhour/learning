<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {

            $order = DB::transaction(function () use ($request) {

                $total = 0;

                $order = Order::create([
                    'total_amount' => 0,
                    'status' => 'pending',
                    'order_date' => now(),
                ]);

                $productIds = collect($request->items)->pluck('product_id');

                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($request->items as $item) {

                    $product = $products->get($item['product_id']);

                    if (!$product) {
                        throw new \Exception("Product not found");
                    }

                    $subtotal = $product->price * $item['quantity'];
                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                    ]);
                }

                $order->update([
                    'total_amount' => $total
                ]);

                $order->load('items.product');

                return $order;
            });

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error creating order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        return response()->json(
            Order::with('items.product', 'payment')->findOrFail($id)
        );
    }

    public function index()
    {
        return response()->json([
            'data' => Order::with('items.product', 'payment')
                ->latest()
                ->paginate(10)
        ]);
    }
}