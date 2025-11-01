<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Promotion;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        log::info('promotion/index');
        $id = $request->query('id');
        $query = Promotion::query();
        if($id){
            $query->where("id", $id);
        }

        $promotion = $query->get()->map(function($promotion){
            return $promotion;
        });

        return response()->json([
            "status" => true,
            "promotion" => $promotion
        ]);
    }

    public function update(Request $request)
    {
        log::info('promotion/update');
        $data = $request->validate([
            'promotions' => 'required|array',
            'promotions.*.id' => 'required|integer|exists:promotions,id',
            'promotions.*.discount' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($data['promotions'] as $promotionData) {
            \App\Models\Promotion::where('id', $promotionData['id'])
                ->update(['discount' => $promotionData['discount']]);
        }

        return response()->json([
            "status" => true,
            "message" => "Product data updated"
        ]);
    }

}
