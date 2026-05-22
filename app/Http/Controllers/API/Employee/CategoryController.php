<?php

namespace App\Http\Controllers\API\Employee;

use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends ApiController
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getCategories();

        return $this->sendResponse(
            array_merge(
                CategoryResource::collection($categories)->toArray(request()),
                ['meta' => pagination_response($categories)]
            ),
            'Category fetched successfully'
        );
    }
}
