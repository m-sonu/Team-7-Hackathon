<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillUploadBatchDetailResource;
use App\Http\Resources\BillUploadBatchResource;
use App\Http\Resources\UserResource;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    /**
     * Display the specified user.
     */
    public function show(int $id): UserResource
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

    public function getUserBills(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $batches = BillUploadBatch::with(['category'])
            ->withSum('bills as bills_sum_approve_amount', 'approve_amount')
            ->withCount([
                'bills as bills_count_pending' => fn ($query) => $query->where('status', Bill::STATUS_PENDING),
                'bills as bills_count_verified' => fn ($query) => $query->where('status', Bill::STATUS_VERIFIED),
                'bills as bills_count_paid' => fn ($query) => $query->where('status', Bill::STATUS_PAID),
                'bills as bills_count_rejected' => fn ($query) => $query->where('status', Bill::STATUS_REJECTED),
            ])
            ->where('user_id', $user->id)
            ->when($request->filled('category_id'), function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->whereHas('bills', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            })
            ->when($request->filled('month'), function ($query) use ($request) {
                $monthInput = $request->month;
                $year = $request->input('year', now()->year);

                // Handle YYYY-MM format if provided
                if (str_contains($monthInput, '-')) {
                    [$year, $monthInput] = explode('-', $monthInput);
                }

                $month = str_pad($monthInput, 2, '0', STR_PAD_LEFT);
                $date = Carbon::createFromFormat('Y-m', "{$year}-{$month}");

                // Billing cycle: 26th previous month → 25th current month
                $start = $date->copy()->subMonth()->day(26)->startOfDay();
                $end = $date->copy()->day(25)->endOfDay();

                return $query->whereBetween('created_at', [$start, $end]);
            })
            ->orderByDesc('bills_count_pending')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return BillUploadBatchResource::collection($batches);
    }

    public function getUserBillsDetails(Request $request, $id)
    {
        $batch = BillUploadBatch::with([
                'category',
                'bills' => function ($q) {
                    $q->without('batch');
                }
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
}
