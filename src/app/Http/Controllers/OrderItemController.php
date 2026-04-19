<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OrderItemController extends Controller
{
    public function store(Request $request, $orderId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {

            $item = DB::transaction(function () use ($request, $orderId) {

                $order = Order::findOrFail($orderId);

                if ($order->status !== 'pending') {
                    throw new \Exception("Order is not editable");
                }

                $product = Product::findOrFail($request->product_id);

                $quantity = (int) $request->quantity;
                $subtotal = $product->price * $quantity;

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);

                $order->increment('total_amount', $subtotal);

                return $item;
            });

            return response()->json([
                'message' => 'Order item created successfully',
                'data' => $item
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to create order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}