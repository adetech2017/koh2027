<?php

namespace App\Http\Controllers\Api;

use App\Models\Merchandise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMerchandiseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Merchandise::active()->with('primaryImage');

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $products = $query->take(20)->get();

        return response()->json(['data' => $products]);
    }
}
