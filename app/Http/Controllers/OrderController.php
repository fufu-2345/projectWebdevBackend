<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $orders = DB::table('orders')
            ->select([
                'id',
                DB::raw('created_at as datetime'),
                'totalprice',
                'promotion',
                'status',
                'user_id',
                'created_at',
                'updated_at',
            ])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'required|string',
        ]);

        $incoming = strtolower(trim($request->input('status')));
        $map = [
            'payment'  => 'payment',
            'shipping' => 'shipping',
            'success'  => 'completed',
            'complete' => 'completed',
            'completed'=> 'completed',
        ];


        if ($incoming === 'payment') {
            $dbStatus = 'wait payment';
        } elseif ($incoming === 'shipping') {
            $dbStatus = 'shipping';
        } elseif (in_array($incoming, ['success','complete','completed'], true)) {
            $dbStatus = 'completed';
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid status'], 422);
        }

        $order->status = $dbStatus;
        $order->save();

        $payload = [
            'id'         => $order->id,
            'datetime'   => $order->created_at,
            'totalprice' => $order->totalprice,
            'promotion'  => $order->promotion,
            'status'     => $order->status,
            'user_id'    => $order->user_id,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];

        return response()->json([
            'status' => true,
            'order'  => $payload,
        ]);
    }

    public function products(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rows = DB::table('items')
            ->join('products', 'products.id', '=', 'items.product_id')
            ->where('items.order_id', $order->id)
            ->get([
                'items.product_id',
                'items.quantity',
                'products.title',
                'products.cost',
            ]);

        $items = $rows->map(function ($r) {
            return [
                'product_id' => (int) $r->product_id,
                'quantity'   => (int) $r->quantity,
                'title'      => $r->title,
                'price'      => (float) $r->cost,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'items'  => $items,
        ]);
    }
}
