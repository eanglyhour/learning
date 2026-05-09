<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use KHQR\BakongKHQR;
use KHQR\Models\IndividualInfo;
use KHQR\Helpers\KHQRData;

class PaymentController extends Controller
{
    // ===================== CHECKOUT =====================
    public function checkout($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);

        if ($order->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order already paid'
            ]);
        }

        try {

            $merchant = new IndividualInfo(
                bakongAccountID: env('BAKONG_ACCOUNT_ID'),
                merchantName: env('BAKONG_MERCHANT_NAME'),
                merchantCity: env('BAKONG_CITY'),
                currency: KHQRData::CURRENCY_KHR,
                billNumber: 'INV-' . $order->id,
                storeLabel: 'Laravel Store'
            );

            $qrResponse = BakongKHQR::generateIndividual($merchant);

            $qrString = data_get($qrResponse, 'data.qr');
            $md5      = strtolower(data_get($qrResponse, 'data.md5'));

            if (!$qrString || !$md5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid KHQR response'
                ], 500);
            }

            $payment = Payment::create([
                'order_id'  => $order->id,
                'amount'    => $order->total_amount,
                'recipient' => env('BAKONG_ACCOUNT_ID'),
                'status'    => 'pending',
                'md5'       => $md5,
                'transaction_id' => null
            ]);

            return response()->json([
                'success'    => true,
                'order_id'   => $order->id,
                'payment_id' => $payment->id,
                'amount'     => $payment->amount,
                'md5'        => $md5,
                'qr'         => $qrString
            ]);

        } catch (\Exception $e) {

            Log::error('KHQR ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'QR generation failed'
            ], 500);
        }
    }

    // ===================== VERIFY =====================
    public function verifyTransaction(Request $request)
    {
        $request->validate([
            'md5' => 'required|string'
        ]);

        $md5 = strtolower($request->md5);

        $payment = Payment::where('md5', $md5)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if ($payment->status === 'success') {
            return response()->json([
                'success' => true,
                'status'  => 'already_paid'
            ]);
        }

        try {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('BAKONG_TOKEN'),
                'Content-Type'  => 'application/json'
            ])
            ->timeout(30)
            ->post(env('BAKONG_API_URL') . 'check_transaction_by_md5', [
                'md5' => $md5
            ]);

            // ================= DEBUG =================
            Log::info('BAKONG RAW RESPONSE', [
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'status'  => 'pending'
                ]);
            }

            $data = $response->json();

            Log::info('BAKONG JSON RESPONSE', [
                'data' => $data
            ]);

            // ================= SAFE PARSING =================
            $responseCode =
                data_get($data, 'responseCode')
                ?? data_get($data, 'data.responseCode')
                ?? data_get($data, 'result.responseCode')
                ?? -1;

            $transactionId =
                data_get($data, 'transactionId')
                ?? data_get($data, 'data.transactionId')
                ?? data_get($data, 'result.transactionId');

            $paidAmount = (float)(
                data_get($data, 'amount')
                ?? data_get($data, 'data.amount')
                ?? data_get($data, 'result.amount')
                ?? 0
            );

            if ($responseCode != 0) {
                return response()->json([
                    'success' => false,
                    'status'  => 'pending'
                ]);
            }

            if (round($paidAmount) != round($payment->amount)) {
                return response()->json([
                    'success' => false,
                    'status'  => 'amount_mismatch'
                ]);
            }

            DB::beginTransaction();

            $order = Order::with('items')
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            if ($order->status === 'paid') {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'status'  => 'already_paid'
                ]);
            }
            foreach ($order->items as $item) {

                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new \Exception("Product not found ID: {$item->product_id}");
                }

                if ($product->stock < $item->quantity) {
                    throw new \Exception("Not enough stock ID: {$item->product_id}");
                }

                $product->decrement('stock', $item->quantity);
            }
            $order->update([
                'status' => 'paid'
            ]);

            $payment->update([
                'status'         => 'success',
                'transaction_id' => $transactionId
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status'  => 'success'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('VERIFY ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed'
            ], 500);
        }
    }
}