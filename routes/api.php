<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\CartController;

// -------- Public (ไม่ต้อง login) --------
Route::get('/hello', function () {
    return response()->json(['message' => 'hello']);
});

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf_token' => csrf_token()])
        ->withCookie(cookie('XSRF-TOKEN', csrf_token(), 120));
});

Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/session', function () {
        return response()->json([
            'role' => Session::get('user_role'),
            'id' => Session::get('user_id'),
            'session_id' => Session::getId()
        ]);
    });
});

Route::post("register", [AuthController::class, "register"]);
Route::get('profile', [ProfileController::class, 'show']);
Route::apiResource("products", ProductController::class);

// ส่งความคืบหน้าครั้งที่ 2
Route::get("getShippingOrders", [AdminController::class, "getShippingOrders"]);
Route::get("getCategorySummary", [AdminController::class, "getCategorySummary"]);
Route::get("getUserOrderSummary", [AdminController::class, "getUserOrderSummary"]);
Route::get("getProductSummary", [AdminController::class, "getProductSummary"]);

// -------- Product detail by id (public) --------
use App\Http\Controllers\DetailProductController ;
Route::get('/products/{productId}/detail',[DetailProductController::class,'show']);


Route::group([
    "middleware" => ["auth:sanctum"]
], function(){
    Route::get("profile", [AuthController::class, "profile"]);
    Route::get("users", [UserController::class, "showUser"]);
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile', [ProfileController::class, 'update']);

    Route::get("updatePro", [PromotionController::class, "update"]);
});

// -------- Authenticated (ต้อง login) --------
// ใน group คือพวกที่ต้อง login แล้วเท่านั้น
// ถ้าใครจะทดสอบ api ใน postman ให้ลองข้างนอก
// หรือ api ที่ใช้งานตอนยังไม่ได้ login ให้ใส่ข้างนอกหมดเลย เหมือน /hello register login ข้างบน
Route::middleware(['auth:sanctum'])->group(function () {

    // Current user info (ให้ FE ใช้ endpoint นี้)
    Route::get('me', function (Request $request) {
        $u = $request->user();
        return response()->json([
            'id'   => $u->id,
            'name' => $u->name,
            'role' => $u->role ?? 'user',
        ]);
    });

    // ออกจากระบบ
    Route::get("logout", [AuthController::class, "logout"]);

    // Users/Promotions (ตัวอย่าง)
    Route::get("users", [UserController::class, "showUser"]);
    Route::get("promotions", [PromotionController::class, "index"]);
    Route::post("updatePromotions", [PromotionController::class, "update"]);
    Route::get("updatePro", [PromotionController::class, "update"]);

    // Orders (ของผู้ใช้ที่ล็อกอิน)
    Route::get('orders',                 [OrderController::class, 'index']);
    Route::patch('orders/{order}',       [OrderController::class, 'updateStatus']); // body: {status}
    Route::get('orders/{order}/products',[OrderController::class, 'products']);

    // Cart (คงชุดเต็มจากไฟล์ที่สอง; ของไฟล์แรกมีเฉพาะ add/get/checkout)
    Route::prefix('cart')->group(function(){
        Route::get('/',         [CartController::class, 'getCart']);
        Route::post('/add',     [CartController::class, 'addToCart']);
        Route::post('/update',  [CartController::class, 'updateQuantity']);
        Route::post('/delete',  [CartController::class, 'deleteItem']);
        Route::post('/checkout',[CartController::class, 'checkout']);
    });
});

// -------- Product detail by id (public) --------

Route::get('promotions',  [PromotionController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::prefix('cart')->group(function(){
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::post('/update', [CartController::class, 'updateQuantity']);
        Route::post('/delete', [CartController::class, 'deleteItem']);
        Route::post('/checkout', [CartController::class, 'checkout']);
        Route::post('/calculate', [CartController::class, 'calculatePromotions']);
    });
});





