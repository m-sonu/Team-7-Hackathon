<?php

namespace App\Http\Controllers\API;

use App\Enums\BillStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeUserBillsRequest;
use App\Http\Resources\BillUploadBatchDetailResource;
use App\Http\Resources\BillUploadBatchResource;
use App\Http\Resources\EmployeeBillResource;
use App\Http\Resources\EmployeeDashboardResource;
use App\Http\Resources\UserResource;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display the specified user.
     */
    public function show(int $id): UserResource|JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return new UserResource($user);
    }

    public function employeeDashboard($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // 1. Setup Date Range (26th of last month to 25th of current month)
        $now = Carbon::now();
        $start = $now->copy()->subMonth()->day(26)->startOfDay();
        $end = $now->copy()->day(25)->endOfDay();

        // 2. Optimization: Get primary stats in a single query from the Bill model
        // We join the batch to filter by the batch's creation date as per your original logic
        $stats = Bill::where('bill.user_id', $user->id)
            ->join('bill_upload_batch as batch', 'bill.bill_upload_batch_id', '=', 'batch.id')
            ->whereBetween('batch.created_at', [$start, $end])
            ->selectRaw('
            COUNT(bill.id) as total_bills,
            SUM(bill.approve_amount) as total_approved_amount,
             SUM(bill.amount) as total_amount,
            COUNT(CASE WHEN bill.status = ? THEN 1 END) as verified_bills_count
        ', [BillStatus::VERIFIED->value])
            ->first();

        // 3. Get Currency (optimized to one query, latest batch)
        $currency = BillUploadBatch::where('user_id', $user->id)
            ->latest()
            ->value('currency') ?? 'NPR';

        // 4. Category Wise Amounts (Cleaned up Join and Selection)
        $categoryWiseAmounts = Category::query()
            ->join('bill as bill', 'category.id', '=', 'bill.category_id')
            ->join('bill_upload_batch as batch', 'bill.bill_upload_batch_id', '=', 'batch.id')
            ->where('bill.user_id', $user->id)
            ->whereBetween('batch.created_at', [$start, $end])
            ->select([
                'category.id as category_id',
                'category.name as category',
                DB::raw('SUM(bill.approve_amount) as approved_amount'),
                DB::raw('SUM(bill.amount) as total_amount'),
                DB::raw('COUNT(bill.id) as bill_count'),
            ])
            ->groupBy('category.id', 'category.name')
            ->get();

        $data = [
            'stats' => $stats,
            'currency' => $currency,
            'category_wise_amounts' => $categoryWiseAmounts,
        ];

        return new EmployeeDashboardResource($data);
    }

    public function getUserBills(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        $month = $request->input('month', Carbon::now()->month);
        $batches = BillUploadBatch::query()
            ->with(['category'])
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->withSum('bills as bills_sum_amount', 'amount')
            ->withCount('bills')
            ->where('user_id', $user->id)
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->where('category_id', $request->category_id)
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->whereHas(
                    'bills',
                    fn ($b) => $b->where('status', $request->status)
                )
            )
            ->when($month, function ($q) use ($request, $month) {
                $year = $request->input('year', now()->year);
                $date = Carbon::create($year, $month, 1);

                $start = $date->copy()->subMonth()->day(26)->startOfDay();
                $end = $date->copy()->day(25)->endOfDay();

                $q->whereBetween('created_at', [$start, $end]);
            })
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return BillUploadBatchResource::collection($batches);
    }

    public function getUserBillsDetails(Request $request, $id)
    {
        $batch = BillUploadBatch::with([
            'category',
            'bills' => function ($q) {
                $q->with('billUploadBatch')
                    ->with('vendorContact');
            },
        ])
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found',
            ], 404);
        }

        return new BillUploadBatchDetailResource($batch);
    }

    public function getEmployeeBills(EmployeeUserBillsRequest $request)
    {
        $month = $request->month ?? now()->month;
        $year = now()->year;

        $selectedMonth = Carbon::create($year, $month, 1);

        $startDate = $selectedMonth->copy()
            ->subMonth()
            ->day(26)
            ->startOfDay();

        $endDate = $selectedMonth->copy()
            ->day(25)
            ->endOfDay();

        $users = Bill::query()
            ->join('users', 'users.id', '=', 'bill.user_id')
            ->leftJoin('bill_upload_batch as batch', 'bill.bill_upload_batch_id', '=', 'batch.id')
            ->where('users.role', UserRole::EMPLOYEE->value)
            ->whereBetween('bill.created_at', [$startDate, $endDate])

            // status filter (optional)
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('bill.status', $request->status);
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('MAX(batch.currency) as currency'),
                DB::raw('MAX(bill.status) as status'),
                DB::raw('SUM(bill.amount) as total_amount'),
                DB::raw('SUM(bill.approve_amount) as total_approve_amount'),
                DB::raw('COUNT(bill.id) as bills_count'),
            ])
            ->latest('users.id')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => EmployeeBillResource::collection($users),
        ]);
    }
}
