<?php

namespace App\Http\Controllers\API;

use App\Enums\AiProcessStatus;
use App\Enums\BillStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeUserBillsRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\BillUploadBatchResource;
use App\Http\Resources\EmployeeBillResource;
use App\Http\Resources\EmployeeDashboardResource;
use App\Http\Resources\UserResource;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use App\Services\AdminBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct(
        private AdminBillService $adminBillService,
    ) {}

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

        // Resolve the current billing cycle month_year using the same logic as BillUploadBatchService
        $now = Carbon::now();
        $cutoff = config('app.billing_cutoff', 26);
        $billingMonth = $now->copy();
        if ($billingMonth->day >= $cutoff) {
            $billingMonth->addMonth();
        }
        $monthYear = $billingMonth->format('Y-m');

        // Fetch all pivot IDs for this user in the current billing cycle.
        // Bills are scoped to these pivots — no date range join needed.
        $pivotIds = CategoryMonthlyPivot::where('user_id', $user->id)
            ->where('month_year', $monthYear)
            ->pluck('id');

        // Primary stats — scoped by pivot IDs, no batch join required
        $stats = Bill::where('user_id', $user->id)
            ->whereIn('category_monthly_pivot_id', $pivotIds)
            ->selectRaw('
                COUNT(id)                                                          AS total_bills,
                SUM(approve_amount)                                                AS total_approved_amount,
                SUM(amount)                                                        AS total_amount,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END)                       AS verified_bills_count
            ', [BillStatus::VERIFIED->value, BillStatus::REIMBURSED->value])
            ->first();

        // Currency from the most recent batch for this user
        $currency = BillUploadBatch::where('user_id', $user->id)
            ->latest()
            ->value('currency') ?? 'NPR';

        // Category-wise amounts — scoped by pivot IDs
        $categoryWiseAmounts = Category::query()
            ->join('bill as bill', 'category.id', '=', 'bill.category_id')
            ->join('bill_upload_batch as batch', 'bill.bill_upload_batch_id', '=', 'batch.id')
            ->where('bill.user_id', $user->id)
            ->where('batch.ai_processing', AiProcessStatus::SUCCESS->value)
            ->whereIn('bill.category_monthly_pivot_id', $pivotIds)
            ->select([
                'category.id as category_id',
                'category.name as category',
                'batch.currency as currency',
                DB::raw('SUM(bill.approve_amount) as approved_amount'),
                DB::raw('SUM(bill.amount) as total_amount'),
                DB::raw('COUNT(bill.id) as bill_count'),
            ])
            ->groupBy('category.id', 'category.name', 'batch.currency')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Employee dashboard fetched',
            'total_bills' => (int) $stats->total_bills,
            'total_approved_amount' => format_currency($stats->total_approved_amount ?? 0, $currency),
            'amount' => format_currency($stats->total_amount ?? 0, $currency),
            'approved_amount' => format_currency($stats->total_approved_amount ?? 0, $currency),
            'current_month_verified_bills' => (int) $stats->verified_bills_count,
            'category_wise_amounts' => EmployeeDashboardResource::collection($categoryWiseAmounts),
            'meta' => pagination_response($categoryWiseAmounts),
        ]);
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

        $batches = BillUploadBatch::query()
            ->with(['category'])
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->withSum('bills as bills_sum_amount', 'amount')
            ->withCount('bills')
            ->withCount(['bills as bills_count_pending' => fn ($q) => $q->where('status', BillStatus::PENDING->value)])
            ->withCount(['bills as bills_count_verified' => fn ($q) => $q->where('status', BillStatus::VERIFIED->value)])
            ->withCount(['bills as bills_count_paid' => fn ($q) => $q->where('status', BillStatus::REIMBURSED->value)])
            ->withCount(['bills as bills_count_rejected' => fn ($q) => $q->where('status', BillStatus::REJECTED->value)])
            ->where('user_id', $user->id)
            ->where('ai_processing', AiProcessStatus::SUCCESS->value)
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->where('category_id', $request->category_id)
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->whereHas('bills', fn ($b) => $b->where('status', $request->status)),
                fn ($q) => $q->whereHas('bills', fn ($b) => $b->whereNot('status', BillStatus::INVALID->value)),
            )
            ->when(
                $request->filled('start_date') && $request->filled('end_date'),
                fn ($q) => $q->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ]),
                function ($q) use ($request) {
                    $month = $request->input('month', Carbon::now()->month);
                    $year = $request->input('year', now()->year);
                    $date = Carbon::create($year, $month, 1);
                    $start = $date->copy()->subMonth()->day(26)->startOfDay();
                    $end = $date->copy()->day(25)->endOfDay();

                    return $q->whereBetween('created_at', [$start, $end]);
                }
            )
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'User bills fetched successfully',
            'data' => BillUploadBatchResource::collection($batches),
            'meta' => pagination_response($batches),
        ]);
    }

    public function getUserBillsDetails(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);

        // 1. Batch details (NO bills eager loading)
        $batch = BillUploadBatch::with('category')
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found',
            ], 404);
        }

        // 2. Paginated bills
        $bills = Bill::where('bill_upload_batch_id', $id)
            ->with(['billUploadBatch', 'vendorContact'])
            ->latest()
            ->paginate($perPage);

        // 3. Response
        return response()->json([
            'success' => true,
            'message' => 'Bills details fetched successfully',
            'id' => $batch->id,
            'title' => $batch->title,
            'category' => $batch->category?->name,
            'created_date' => $batch->created_at->format('M d y'),
            'approved_amount' => format_currency(
                $batch->bills_sum_approve_amount ?? 0,
                $batch->currency
            ),
            'data' => BillResource::collection($bills),
            'meta' => pagination_response($bills),
        ]);
    }

    public function getEmployeeBills(EmployeeUserBillsRequest $request): JsonResponse
    {
        $month = $request->integer('month', now()->month);
        $year = now()->year;
        $selectedMonth = Carbon::create($year, $month, 1);
        $startDate = $selectedMonth->copy()->subMonth()->day(26)->startOfDay();
        $endDate = $selectedMonth->copy()->day(config('app.billing_cutoff', 25))->endOfDay();

        $status = BillStatus::tryFrom((string) $request->input('status', ''));

        $users = $this->adminBillService->getEmployeeBillsSummary(
            startDate: $startDate,
            endDate: $endDate,
            status: $status,
            perPage: $request->integer('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'employee bills fetched successfully',
            'month' => $month,
            'year' => $year,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => EmployeeBillResource::collection($users),
            'meta' => pagination_response($users),
        ]);
    }
}
