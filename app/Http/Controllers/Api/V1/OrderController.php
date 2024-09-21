<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::query()
            ->with(['customer','orderdetails', 'orderdetails.product', 'orderdetails.product.images', 'orderdetails.stoke'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'users all orders',
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {

        if (Auth::check()) {

            $request->validate([
                'addressId' => 'required',
                'paymentMethod' => 'required',
                'orderTotal' => 'required',
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:products,id',
            ], [
                'orders.*.data.id.required' => 'The product  ID is required for each order',
                'orders.*.data.id.exists' => 'The selected product ID does not exist.',
            ]);

            $address = Address::with('orderArea')->findOrFail($request->addressId);
            $grandTotal = $address->orderArea->delevery_charge + $request->orderTotal;


            $order = Order::create([
                'transaction_id' => rand(111111, 999999),
                'user_id' => $request->user()->id,
                'address_id' => $request->input('addressId'),
                'payment_method' => $request->input('paymentMethod'),
                'sub_total' => $request->orderTotal,
                'grand_total' => $grandTotal,
                'order_date' => Carbon::now(),
            ]);

            $orderDetails = [];
            foreach ($request->orders as $key => $item) {
                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item["buyQty"] ?? 1,
                    'product_variant' => json_encode([...$item['variant'], 'message' => $item['message'] ?? '']),
                    'product_price' => $item['price']
//                    'product_stock_id' => 1
                ];
//                if (!empty($item["selectSku"]) && !empty($item["selectSku"]["id"])) {
//                    $stock = ProductStock::where('id', $item["selectSku"]["id"])->first();
//                    if ($stock) {
//                        $stock->qty = $stock->qty - $item["selectSku"]["selectQty"];
//                        $stock->save();
//                    }
//                }
//                if (!empty($item["selectSku"]) && !empty($item["selectSku"]["sku"])) {
//                    $product = Product::query()->whereSku($item["selectSku"]["sku"])->first();
//                    if ($product) {
//                        $product->stock = $product->stock - $item["selectSku"]["selectQty"];
//                        $product->save();
//                    }
//                }
            }
            $order->orderdetails()->createMany($orderDetails);

            if ($request->input('paymentMethod') == 'stripe') {
                $stripe = new StripeController();

                $session = $stripe->checkout($order, $orderDetails);
                $order->transaction_id = $session->id;
                $order->save();

                return response()->json([
                    'type' => 'stripe_payment',
                    'message' => 'Order save successfully done.',
                    'data' => $session->url,
                ], 200);
            } else {
                return response()->json([
                    'type' => 'cache_on_delivery',
                    'message' => 'Order save successfully done.',
                    'data' => $order,
                ], 200);
            }
        }

        throw new AuthenticationException();

    }

    public function show($id)
    {
        $order = Order::query()
            ->with(['customer', 'address.orderArea', 'orderDetails.product'])
            ->findOrFail($id);
        return response()->json($order);
    }

    public function orderDetails($id)
    {
        $order = Order::with(['orderdetails', 'orderdetails.product', 'orderdetails.stoke', 'customer', 'address.orderArea'])->findOrFail($id);
        return response()->json($order, 200);
    }

    public function update(Request $request, Order $order)
    {
        //
    }

    public function destroy(Order $order)
    {
        $order->orderdetails()->delete();
        $order->delete();
        return response()->json("Order Deleted...", 200);
    }


    public function changeOrderStatus(Request $request)
    {
        $order = Order::findOrFail($request->id);
        if ($request->input('type') == 'payment') {
            $order->payment_status = Str::lower($request->input('status'));
        } else {
            $order->order_status = Str::lower($request->input('status'));
        }
        $order->update();
        return response()->json(['message' => 'Status Updated...']);
    }

    public function changePaymentStatus()
    {
        return dd(\request()->all());
    }


}
