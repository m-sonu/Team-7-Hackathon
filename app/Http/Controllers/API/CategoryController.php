<?php

namespace App\Http\Controllers\API;

use App\Enums\BillStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserCategoryBillsRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\CategoryBillResource;
use App\Http\Resources\CategoryResource;
use App\Models\Bill;
use App\Models\Category;
use App\Services\AdminBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function __construct(protected AdminBillService $billService) {}

    public function index()
    {
        $category = Category::select('id', 'name', 'monthly_limit', 'is_active')
            ->orderBy('name', 'asc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Category fetched successfully',
            'data' => CategoryResource::collection($category),
            'meta' => pagination_response($category),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    public function getUserCategoryWiseBillDetails(Request $request, $userId, $categoryId)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', now()->year);

        $date = Carbon::create($year, $month, 1);

        $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
        $endDate = $date->copy()->day(25)->endOfDay();

        $statusOrder = [
            BillStatus::UNDER_REVIEW->value,
            BillStatus::VERIFIED->value,
            BillStatus::REJECTED->value,
            BillStatus::REIMBURSED->value,
        ];

        $bills = Bill::with(['category', 'billUploadBatch'])
            ->where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', BillStatus::adminBillStatuses())
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 ELSE 5 END',
                $statusOrder
            )
            ->paginate(10);
        $currency = $bills->first()?->billUploadBatch?->currency;

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

    public function getUserCategoryWiseBills(
        UserCategoryBillsRequest $request,
        int $userId,
    ): JsonResponse {
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
