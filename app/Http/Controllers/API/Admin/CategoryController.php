<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Category\DestroyCategoryAction;
use App\Actions\Category\StoreCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\UserCategoryBillsRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\CategoryBillResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Admin\AdminCategoryService;
use App\Services\AdminBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CategoryController extends ApiController
{
    public function __construct(
        private AdminCategoryService $categoryService,
        private AdminBillService $billService,
    ) {}

    public function store(StoreCategoryRequest $request, StoreCategoryAction $action): JsonResponse
    {
        $category = $action->execute($request->validated());

        return $this->sendResponse(
            (new CategoryResource($category))->resolve(),
            'Category created successfully',
            201
        );
    }

    public function update(StoreCategoryRequest $request, Category $category, UpdateCategoryAction $action): JsonResponse
    {
        $category = $action->execute($category, $request->validated());

        return $this->sendResponse(
            (new CategoryResource($category))->resolve(),
            'Category updated successfully'
        );
    }

    public function destroy(Category $category, DestroyCategoryAction $action): JsonResponse
    {
        $action->execute($category);

        return $this->sendResponse([], 'Category deleted successfully');
    }

    public function getUserCategoryWiseBillDetails(UserCategoryBillsRequest $request, int $userId, int $categoryId): JsonResponse
    {
        $result = $this->categoryService->getCategoryBillDetails(
            userId: $userId,
            categoryId: $categoryId,
            month: $request->month(),
            year: $request->year(),
            startDate: $request->startDate(),
            endDate: $request->endDate(),
        );

        $bills = $result['bills'];
        $currency = $result['currency'];
        $updatedCategoryLimit = $result['updated_category_limit'];

        $request->attributes->set('updated_category_limit', $updatedCategoryLimit);

        return $this->sendResponse([
            'total_amount' => format_currency($bills->sum('amount'), $currency ?? ''),
            'approve_amount' => format_currency($bills->sum('approve_amount'), $currency ?? ''),
            'bill_count' => $bills->count(),
            'data' => BillResource::collection($bills),
            'meta' => pagination_response($bills),
        ], 'Category wise bills details fetched successfully');
    }

    public function getUserCategoryWiseBills(UserCategoryBillsRequest $request, int $userId): JsonResponse
    {
        $results = $this->billService->getCategoryWiseBillsForUser(
            userId: $userId,
            month: $request->month(),
            year: $request->year(),
            startDate: $request->startDate(),
            endDate: $request->endDate(),
            status: $request->status(),
        );

        return $this->sendResponse([
            'user_id' => $userId,
            'data' => CategoryBillResource::collection($results),
        ], 'User category wise bills fetched successfully');
    }
}
