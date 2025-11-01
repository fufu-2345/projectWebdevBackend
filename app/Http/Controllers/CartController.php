<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Item;
use App\Models\Product;
use App\Models\Promotion;

class CartController extends Controller {
    // Add to cart
    public function addToCart(Request $request) {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
        ]);

        $user = $request->user();
        // สร้าง order 
        $order = Order::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'in cart'],
            ['totalprice' => 0]
        );
        // check item in cart
        $item = Item::where('order_id', $order->id)
                    ->where('product_id', $request->product_id)
                    ->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            Item::create([
                'order_id' => $order->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Item added to cart'
        ]);
    }
    // Get cart
    public function getCart(Request $request) {
        $user = $request->user();

        $order = Order::with('items.product')
            ->where('user_id', $user->id)
            ->where('status', 'in cart')
            ->first();

        return response()->json(['status' => true, 'cart' => $order]);
    }
    // Update quantity
    public function updateQuantity(Request $request){
        $request->validate([
            'item_id'  => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $item = Item::with('order', 'product')->find($request->item_id);

        if(!$item){
            return response()->json(['status'=>false,'message'=>'Item not found']);
        }

        if($item->order->user_id != $user->id || $item->order->status != 'in cart'){
            return response()->json(['status'=>false,'message'=>'Unauthorized']);
        }

        if($request->quantity > $item->product->stock){
            return response()->json(['status'=>false,'message'=>'Exceeds stock']);
        }

        $item->quantity = $request->quantity;
        $item->save();

        return response()->json(['status'=>true,'message'=>'Quantity updated']);
    }
    // Delete item
    public function deleteItem(Request $request){
        $request->validate([
            'item_id' => 'required|integer',
        ]);

        $user = $request->user();
        $item = Item::with('order')->find($request->item_id);

        if(!$item){
            return response()->json(['status'=>false,'message'=>'Item not found']);
        }

        if($item->order->user_id != $user->id || $item->order->status != 'in cart'){
            return response()->json(['status'=>false,'message'=>'Unauthorized']);
        }

        $item->delete();
        return response()->json(['status'=>true,'message'=>'Item deleted']);
    }
    // Checkout
    public function checkout(Request $request){
        $user = $request->user();

        $order = Order::with('items.product')
                    ->where('user_id', $user->id)
                    ->where('status','in cart')
                    ->first();

        if (!$order || $order->items->isEmpty()) {
            return response()->json([
                'status'=>false,
                'message'=>'Cart is empty',
                'cart'=>null
            ]);
        }

        $promoBuy2Discount = Promotion::find(1)?->discount ?? 0; 
        $promoDateDiscount = Promotion::find(2)?->discount ?? 0; 
        $totalPrice = 0;
        $today = now();
        $isSameDayMonth = ($today->day === $today->month);
        $appliedPromotionIds = []; 

        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product) continue;

            $price = $product->cost;
            $qty = $item->quantity;
            $subtotal = 0;

            if ($qty > $product->stock) {
                return response()->json([
                    'status'=>false,
                    'message'=>"Insufficient stock for product: {$product->title}",
                    'cart'=>null
                ]);
            }

            if ($product->id >= 1 && $product->id <= 5) {
                $pairs = intdiv($qty, 2);
                $remainder = $qty % 2;
                $discountedPrice = $price * (1 - $promoBuy2Discount / 100);
                $subtotal = ($pairs * 2 * $discountedPrice) + ($remainder * $price);
                if ($pairs > 0) $appliedPromotionIds[] = 1;
            } 
            elseif ($product->id >= 6 && $product->id <= 8) {
                $freeItems = intdiv($qty, 3);
                $payQty = $qty - $freeItems;
                $subtotal = $payQty * $price;
                if ($freeItems > 0) $appliedPromotionIds[] = 3;
            } 
            else {
                $subtotal = $price * $qty;
            }

            $totalPrice += $subtotal;
            Product::find($item->product_id)->decrement('stock', $item->quantity);
        }

        if ($isSameDayMonth) {
            $totalPrice = $totalPrice * (1 - $promoDateDiscount / 100);
            $appliedPromotionIds[] = 2; 
        }

        $uniquePromotionIds = array_unique($appliedPromotionIds);
        $promotionData = json_encode($uniquePromotionIds);

        $order->update([
            'totalprice' => round($totalPrice, 2),
            'promotion' => $promotionData,
            'status' => 'wait payment',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Order placed successfully',
            'totalprice' => round($totalPrice, 2),
            'applied_promotions_ids' => $uniquePromotionIds, 
        ]);
    }

    public function calculatePromotions(Request $request) {
    $user = $request->user();

    $order = Order::with('items.product')
                ->where('user_id', $user->id)
                ->where('status','in cart')
                ->first();

    if (!$order || $order->items->isEmpty()) {
        return response()->json([
            'status'=>false,
            'message'=>'Cart is empty',
            'totalprice'=>0,
            'applied_promotions_ids'=>[]
        ]);
    }

    $promoBuy2Discount = Promotion::find(1)?->discount ?? 0; 
    $promoDateDiscount = Promotion::find(2)?->discount ?? 0; 
    $totalPrice = 0;
    $today = now();
    $isSameDayMonth = ($today->day === $today->month);
    $appliedPromotionIds = []; 

    foreach ($order->items as $item) {
        $product = $item->product;
        if (!$product) continue;

        $price = $product->cost;
        $qty = $item->quantity;
        $subtotal = $price * $qty;

        // โปรโมชั่นซื้อ 2 ถูกกว่า
        if ($product->id >= 1 && $product->id <= 5) {
            $pairs = intdiv($qty, 2);
            $remainder = $qty % 2;
            $discountedPrice = $price * (1 - $promoBuy2Discount / 100);
            $subtotal = ($pairs * 2 * $discountedPrice) + ($remainder * $price);
            if ($pairs > 0) $appliedPromotionIds[] = 1;
        }

        // โปรโมชั่นซื้อ 2 แถม 1
        if ($product->id >= 6 && $product->id <= 8) {
            $freeItems = intdiv($qty, 3);
            $payQty = $qty - $freeItems;
            $subtotal = $payQty * $price;
            if ($freeItems > 0) $appliedPromotionIds[] = 3;
        }

        $totalPrice += $subtotal;
    }

    // โปรโมชั่นวันและเดือนตรงกัน
    if ($isSameDayMonth) {
        $totalPrice = $totalPrice * (1 - $promoDateDiscount / 100);
        $appliedPromotionIds[] = 2; 
    }

    // ลบซ้ำ
    $uniquePromotionIds = array_unique($appliedPromotionIds);

    return response()->json([
        'status' => true,
        'message' => 'Promotion calculated successfully',
        'totalprice' => round($totalPrice, 2),
        'applied_promotions_ids' => $uniquePromotionIds,
    ]);
}


}
    
