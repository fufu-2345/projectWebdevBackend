<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Product;
use App\Models\Item;
use App\Models\History;


class AdminController extends Controller
{
    public function index()
    {
        Log::info("Role from Route: " . $role);
Log::info("User's Role: " . $user->role);
        return response()->json([

            'message' => 'hello test test'
        ]);
    }

    public function test()
    {
        Log::info("Role from Route: " . $role);
        Log::info("User's Role: " . $user->role);
        Log::info('User from Auth:', [$user]);
        return response()->json([
            'message' => 'test test'
        ]);
    }


    /////// ส่งความคืบหน้าครั้งที่ 2
    public function getShippingOrders() //2
    {
        $orders = User::join('orders', 'users.id', '=', 'orders.user_id')
            ->join('items', 'orders.id', '=', 'items.order_id')
            ->where('orders.status', 'shipping')
            ->select(
                'users.id as user_id',
                'users.name as username',
                'orders.updated_at as order_at',
                'orders.totalprice as total_price',
                DB::raw('SUM(items.quantity) as quantity'),
                'users.address as address',
                'users.email as email',
                'users.phone as phone'
            )
            ->groupBy('orders.id')
            ->get();

        return response()->json($orders);
    }

    public function getCategorySummary(Request $request)
{
    $startMonth = $request->input('startMonth');
    $endMonth = $request->input('endMonth');
    $year = $request->input('year', date('Y'));
    $category = $request->input('category');

    $query = DB::table('items')
        ->join('products', 'items.product_id', '=', 'products.id')
        ->join('orders', 'items.order_id', '=', 'orders.id')
        ->select(
            'products.category as Category',
            DB::raw('SUM(items.quantity) as TotalQuantity'),
            DB::raw('SUM(orders.totalprice) as TotalPrice'),
            DB::raw('COUNT(DISTINCT orders.id) as TotalOrder'),
            DB::raw('MONTH(orders.updated_at) as Month')
        )
        ->where('orders.status', 'completed')
        ->whereYear('orders.updated_at', $year)
        ->whereBetween(DB::raw('MONTH(orders.updated_at)'), [$startMonth, $endMonth]);

        if ($category !== null && $category !== '') {
            $query->where('products.category', $category);
        }

        $result = $query
            ->groupBy('products.category', DB::raw('MONTH(orders.updated_at)'))
            ->get();

        $grouped = $result->groupBy('Category')->map(function ($items) use ($startMonth, $endMonth) {
            $monthlyPrices = [];
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $monthData = $items->firstWhere('Month', $m);
                $monthlyPrices[] = $monthData ? $monthData->TotalPrice : 0;
            }
            return [
                'Category' => $items[0]->Category,
                'TotalQuantity' => $items->sum('TotalQuantity'),
                'TotalPrice' => $items->sum('TotalPrice'),
                'TotalOrder' => $items->sum('TotalOrder'),
                'MonthlyPrices' => $monthlyPrices,
            ];
        })->values();

        return response()->json($grouped);
    }

    public function getUserOrderSummary(Request $request)
    {
        $startMonth = $request->input('startMonth');
        $endMonth = $request->input('endMonth');
        $year = $request->input('year', date('Y'));

        $result = DB::table('items')
            ->join('products', 'items.product_id', '=', 'products.id')
            ->join('orders', 'items.order_id', '=', 'orders.id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select(
                'users.name as Username',
                DB::raw('SUM(items.quantity) as TotalQuantity'),
                DB::raw('SUM(orders.totalprice) as TotalPrice'),
                DB::raw('COUNT(DISTINCT orders.id) as TotalOrder'),
                DB::raw('MONTH(orders.updated_at) as Month')
            )
            ->where('orders.status', 'completed')
            ->whereYear('orders.updated_at', $year)
            ->whereBetween(DB::raw('MONTH(orders.updated_at)'), [$startMonth, $endMonth])
            ->groupBy('users.name', DB::raw('MONTH(orders.updated_at)'))
            ->get();

        $grouped = $result->groupBy('Username')->map(function ($items) use ($startMonth, $endMonth) {
            $monthlyPrices = [];
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $monthData = $items->firstWhere('Month', $m);
                $monthlyPrices[] = $monthData ? $monthData->TotalPrice : 0;
            }
            return [
                'Username' => $items[0]->Username,
                'TotalQuantity' => $items->sum('TotalQuantity'),
                'TotalPrice' => $items->sum('TotalPrice'),
                'TotalOrder' => $items->sum('TotalOrder'),
                'MonthlyPrices' => $monthlyPrices,
            ];
        })->values();

        return response()->json($grouped);
    }

    public function getProductSummary(Request $request)
    {
        $startMonth = $request->input('startMonth');
        $endMonth = $request->input('endMonth');
        $year = $request->input('year', date('Y'));

        $query = DB::table('items')
            ->join('products', 'items.product_id', '=', 'products.id')
            ->join('orders', 'items.order_id', '=', 'orders.id')
            ->select(
                'products.title as Productname',
                DB::raw('SUM(items.quantity) as TotalQuantity'),
                DB::raw('SUM(orders.totalprice) as TotalPrice'),
                DB::raw('COUNT(DISTINCT orders.id) as TotalOrder'),
                DB::raw('MONTH(orders.updated_at) as Month')
            )
            ->where('orders.status', 'completed')
            ->whereYear('orders.updated_at', $year)
            ->whereBetween(DB::raw('MONTH(orders.updated_at)'), [$startMonth, $endMonth]);

        $result = $query
            ->groupBy('products.title', DB::raw('MONTH(orders.updated_at)'))
            ->get();

        $grouped = $result->groupBy('Productname')->map(function ($items) use ($startMonth, $endMonth) {
            $monthlyPrices = [];
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $monthData = $items->firstWhere('Month', $m);
                $monthlyPrices[] = $monthData ? $monthData->TotalPrice : 0;
            }
            return [
                'Productname' => $items[0]->Productname,
                'TotalQuantity' => $items->sum('TotalQuantity'),
                'TotalPrice' => $items->sum('TotalPrice'),
                'TotalOrder' => $items->sum('TotalOrder'),
                'MonthlyPrices' => $monthlyPrices,
            ];
        })->values();

        return response()->json($grouped);
    }


    ///////
}
