<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        log::info('products/index');
        $category = $request->query('category');
        $query = Product::query();
        if($category){
            $query->where("category", $category);
        }
        $query->where('stock', '!=', -1000);

        $products = $query->get()->map(function($product){
            $product->banner_image = $product->banner_image ? asset("storage/" . $product->banner_image) : null;
            return $product;
        });

        return response()->json([
            "status" => true,
            "products" => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('products/store', $request->all());
        $data = $request -> validate([
            "title" => "required",
            "cost" => "required|integer",
            "category" => "required",
            "stock" => "required|integer"
        ]);

        if($request->hasFile("banner_image")){
            $data["banner_image"] = $request->file("banner_image")->store("products", "public");
        }

        Product::create($data);

        return response()->json([
            "status" => true,
            "message" => "Product create successfully"
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            "status" => true,
            "messenger" => "Product data found",
            "products" => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        log::info('products/update');
        $data = $request -> validate([
            "title" => "required"
        ]);
        $category = isset($request->category) ? $request->category : $product->category;
        $data["cost"] = isset($request->cost) ? $request->cost : $product->cost;
        $data["stock"] = isset($request->stock) ? $request->stock : $product->stock;

        if($request->hasFile("banner_image")){
            if($product->banner_image){
                Storage::disk("public")->delete($product->banner_image);
            }

            $data["banner_image"] = $request->file("banner_image")->store("products","public");
        }
        $product->update($data);

        return response()->json([
            "status" => true,
            "message" => "Product data updated"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            "status" => true,
            "message" => "Product deleted successfully"
        ]);
    }
}
