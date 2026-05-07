<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Http\Resources\UserResource;
use App\Models\Bill;
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
        $userId = User::find($id)->id;
        $bills = Bill::with(['category', 'billUploadBatch.category'])
            ->where('user_id', $userId)
            ->when($request->filled('category_id'), function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->filled('month'), function ($query) use ($request) {
                $monthInput = $request->month;
                $year = $request->input('year', now()->year);

                // Normalize month to 2 digits
                $month = str_pad($monthInput, 2, '0', STR_PAD_LEFT);

                $date = Carbon::createFromFormat('Y-m', "{$year}-{$month}");

                // Billing cycle: 26th previous month → 25th current month
                $start = $date->copy()->subMonth()->day(26)->startOfDay();
                $end = $date->copy()->day(25)->endOfDay();

                return $query->whereBetween('created_at', [$start, $end]);
            })
            ->oldest()
            ->paginate($request->input('per_page', 15));

        return BillResource::collection($bills);
    }
}
