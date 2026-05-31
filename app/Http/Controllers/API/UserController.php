<?php

namespace App\Http\Controllers\API;

use App\Enums\AiProcessStatus;
use App\Enums\BillStatus;
use App\Http\Controllers\ApiController;
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

class UserController extends ApiController
{
    public function __construct(
        private AdminBillService $adminBillService,
    ) {}

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return $this->sendError('User not found', 404);
        }

        return $this->sendResponse((new UserResource($user))->resolve(), 'success');
    }

    public function employeeDashboard(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->sendError('User not found', 404);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();

            $billQuery = Bill::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', [BillStatus::FAILED, BillStatus::INVALID]);

            $categoryFilter = fn ($q) => $q
                ->where('bill.user_id', $user->id)
                ->whereBetween('bill.created_at', [$startDate, $endDate])
                ->where('batch.ai_processing', AiProcessStatus::SUCCESS->value);
        } else {
            $now = Carbon::now();
            $cutoff = config('app.billing_cutoff', 26);
            $billingMonth = $now->copy();
            if ($billingMonth->day >= $cutoff) {
                $billingMonth->addMonth();
            }
            $monthYear = $billingMonth->format('Y-m');

            $pivotIds = CategoryMonthlyPivot::where('user_id', $user->id)
                ->where('month_year', $monthYear)
                ->pluck('id');

            $billQuery = Bill::where('user_id', $user->id)
                ->whereIn('category_monthly_pivot_id', $pivotIds)
                ->whereNotIn('status', [BillStatus::FAILED, BillStatus::INVALID]);

            $categoryFilter = fn ($q) => $q
                ->where('bill.user_id', $user->id)
                ->whereIn('bill.category_monthly_pivot_id', $pivotIds)
                ->where('batch.ai_processing', AiProcessStatus::SUCCESS->value);
        }

        $stats = $billQuery
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

        $categoryWiseAmounts = Category::query()
            ->join('bill as bill', 'category.id', '=', 'bill.category_id')
            ->join('bill_upload_batch as batch', 'bill.bill_upload_batch_id', '=', 'batch.id')
            ->where($categoryFilter)
            ->select([
                'category.id as category_id',
                'category.name as category_en',
                'category.jp_name as category_jp',
                'batch.currency as currency',
                DB::raw('SUM(bill.approve_amount) as approved_amount'),
                DB::raw('SUM(bill.amount) as total_amount'),
                DB::raw('COUNT(bill.id) as bill_count'),
            ])
            ->groupBy('category.id', 'category.name', 'category.jp_name', 'batch.currency')
            ->paginate(10);

        return $this->sendResponse([
            'total_bills' => (int) $stats->total_bills,
            'total_approved_amount' => format_currency($stats->total_approved_amount ?? 0, $currency),
            'amount' => format_currency($stats->total_amount ?? 0, $currency),
            'approved_amount' => format_currency($stats->total_approved_amount ?? 0, $currency),
            'current_month_verified_bills' => (int) $stats->verified_bills_count,
            'category_wise_amounts' => EmployeeDashboardResource::collection($categoryWiseAmounts)->toArray(request()),
            'meta' => pagination_response($categoryWiseAmounts),
        ], 'Employee dashboard fetched');
    }

    public function getUserBills(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->sendError('User not found', 404);
        }

        $batches = BillUploadBatch::query()
            ->with(['category'])
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->withSum('bills as bills_sum_amount', 'amount')
            ->withCount('bills')
            ->withCount(['bills as bills_count_pending' => fn ($q) => $q->where('status', BillStatus::PENDING->value)])
            ->withCount(['bills as bills_count_under_review' => fn ($q) => $q->where('status', BillStatus::UNDER_REVIEW->value)])
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
                fn ($q) => $q->whereHas('bills', fn ($b) => $b->whereNotIn('status', [BillStatus::INVALID->value,BillStatus::FAILED->value])),
            )
            ->when(
                $request->filled('start_date') && $request->filled('end_date'),
                fn ($q) => $q->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ]),
                function ($q) use ($request) {
                    $monthInput = $request->input('month');
                    if ($monthInput && str_contains((string) $monthInput, '-')) {
                        $date = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
                    } else {
                        $cutoff = config('app.billing_cutoff', 26);
                        $now = Carbon::now();
                        if ($now->day >= $cutoff) {
                            $now->addMonth();
                        }
                        $date = Carbon::create(
                            $request->input('year', $now->year),
                            $monthInput ?? $now->month,
                            1
                        );
                    }
                    $start = $date->copy()->subMonth()->day(26)->startOfDay();
                    $end = $date->copy()->day(25)->endOfDay();

                    return $q->whereBetween('created_at', [$start, $end]);
                }
            )
            ->orderByDesc('created_at')
//            ->whereNotIn('status', [BillStatus::INVALID->value, BillStatus::FAILED->value])
            ->paginate($request->input('per_page', 10));

        return $this->sendResponse(
            array_merge(
                BillUploadBatchResource::collection($batches)->toArray(request()),
                ['meta' => pagination_response($batches)]
            ),
            'User bills fetched successfully'
        );
    }

    public function getUserBillsDetails(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);

        // 1. Batch details (NO bills eager loading)
        $batch = BillUploadBatch::with('category')
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->find($id);

        if (! $batch) {
            return $this->sendError('Batch not found', 404);
        }

        // 2. Paginated bills
        $bills = Bill::where('bill_upload_batch_id', $id)
            ->with(['billUploadBatch', 'vendorContact'])
            ->whereNotIn('status', [BillStatus::INVALID->value, BillStatus::FAILED->value])
            ->latest()
            ->paginate($perPage);

        // 3. Response
        return $this->sendResponse([
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
        ], 'Bills details fetched successfully');
    }

    public function getEmployeeBills(EmployeeUserBillsRequest $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

        } else {
            $month = $request->integer('month', now()->month);
            $year = now()->year;
            $selectedMonth = Carbon::create($year, $month, 1);
            $startDate = $selectedMonth->copy()->subMonth()->day(26)->startOfDay();
            $endDate = $selectedMonth->copy()->day(config('app.billing_cutoff', 25))->endOfDay();
        }

        $status = BillStatus::tryFrom((string) $request->input('status', ''));
        $users = $this->adminBillService->getEmployeeBillsSummary(
            startDate: $startDate,
            endDate: $endDate,
            status: $status,
            perPage: $request->integer('per_page', 15),
        );

        return $this->sendResponse([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => EmployeeBillResource::collection($users),
            'meta' => pagination_response($users),
        ], 'employee bills fetched successfully');
    }
}
