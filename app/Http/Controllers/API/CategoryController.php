<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Category;
use App\Models\Bill;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryBillResource;
use App\Http\Resources\BillResource;
use DB;
class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::select('id', 'name', 'monthly_limit','is_active')
            -> orderBy('name','asc')
            ->paginate(10);
        return CategoryResource::collection($category);
    }
    public function getUserCategoryWiseBillDetails(Request $request, $userId, $categoryId)
{
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', now()->year);

    $date = Carbon::create($year, $month, 1);

    $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
    $endDate = $date->copy()->day(25)->endOfDay();

    $bills = Bill::with(['category', 'billUploadBatch'])
        ->where('user_id', $userId)
        ->where('category_id', $categoryId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
        $currency = $bills->first()?->billUploadBatch?->currency;
   
    return response()->json([
        'success' => true,
        'total_amount' => format_currency($bills->sum('amount'), $currency ?? ''),
        'approve_amount' => format_currency($bills->sum('approve_amount'), $currency ?? ''),
        'bill_count' => $bills->count(),
        'data' => BillResource::collection($bills),
    
    ]);
}

public function getUserCategoryWiseBills(Request $request, $userId)
{
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', now()->year);

    $date = Carbon::create($year, $month, 1);

    $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
    $endDate = $date->copy()->day(25)->endOfDay();
 $results = DB::select("
    WITH base AS (
        SELECT *
        FROM bill
        WHERE user_id = ?
        AND created_at BETWEEN ? AND ?
    ),

    category_summary AS (
        SELECT
            category_id,
            COUNT(*) AS bill_count,
            SUM(amount) AS total_amount,
            SUM(approve_amount) AS approved_amount,
            MAX(bill_upload_batch_id) AS latest_batch_id
        FROM base
        GROUP BY category_id
    ),

    status_ranked AS (
        SELECT
            category_id,
            status,
            COUNT(*) AS count,
            SUM(amount) AS total_amount,
            SUM(approve_amount) AS approved_amount
        FROM base
        GROUP BY category_id, status
    ),

    highest_status AS (
        SELECT DISTINCT ON (category_id)
            category_id,
            status
        FROM base
        ORDER BY category_id,
        CASE status
            WHEN 'Pending' THEN 1
            WHEN 'Under Review' THEN 2
            WHEN 'Verified' THEN 3
            WHEN 'Rejected' THEN 4
            WHEN 'Paid' THEN 5
            ELSE 0
        END DESC
    )

    SELECT
        cs.category_id,
        c.name as category_name,
        cs.bill_count,
        cs.total_amount,
        cs.approved_amount,

        hs.status as highest_status,

        b.currency as currency

    FROM category_summary cs
    JOIN category c ON c.id = cs.category_id
    LEFT JOIN highest_status hs ON hs.category_id = cs.category_id
    LEFT JOIN bill_upload_batch b ON b.id = cs.latest_batch_id
", [$userId, $startDate, $endDate]);

    return response()->json([
        'success' => true,
        'user_id' => $userId,
        'period' => [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ],
        'data' => CategoryBillResource::collection($results),
    ]);
   
}
}