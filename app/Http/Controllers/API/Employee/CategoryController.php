<?php

namespace App\Http\Controllers\API\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getCategories();

        return response()->json([
            'success' => true,
            'message' => 'Category fetched successfully',
            'data' => CategoryResource::collection($categories),
            'meta' => pagination_response($categories),
        ]);
    }
}
