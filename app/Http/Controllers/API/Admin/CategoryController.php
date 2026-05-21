<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Category\DestroyCategoryAction;
use App\Actions\Category\StoreCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserCategoryBillsRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\CategoryBillResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Admin\AdminCategoryService;
use App\Services\AdminBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function __construct(
        private AdminCategoryService $categoryService,
        private AdminBillService $billService,
    ) {}

    public function store(StoreCategoryRequest $request, StoreCategoryAction $action): JsonResponse
    {
        $category = $action->execute($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(StoreCategoryRequest $request, Category $category, UpdateCategoryAction $action): JsonResponse
    {
        $category = $action->execute($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category, DestroyCategoryAction $action): JsonResponse
    {
        $action->execute($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    public function getUserCategoryWiseBillDetails(Request $request, int $userId, int $categoryId): JsonResponse
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $result = $this->categoryService->getCategoryBillDetails($userId, $categoryId, $month, $year);

        $bills = $result['bills'];
        $currency = $result['currency'];
        $updatedCategoryLimit = $result['updated_category_limit'];

        $request->attributes->set('updated_category_limit', $updatedCategoryLimit);

        return response()->json([
            'success' => true,
            'message' => 'Category wise bills details fetched successfully',
            'total_amount' => format_currency($bills->sum('amount'), $currency ?? ''),
            'approve_amount' => format_currency($bills->sum('approve_amount'), $currency ?? ''),
            'bill_count' => $bills->count(),
            'data' => BillResource::collection($bills),
            'meta' => pagination_response($bills),
        ]);
    }

    public function getUserCategoryWiseBills(UserCategoryBillsRequest $request, int $userId): JsonResponse
    {
        $results = $this->billService->getCategoryWiseBillsForUser(
            userId: $userId,
            month: $request->month(),
            year: $request->year(),
        );

        return response()->json([
            'success' => true,
            'message' => 'User category wise bills fetched successfully',
            'user_id' => $userId,
            'data' => CategoryBillResource::collection($results),
        ]);
    }
}
