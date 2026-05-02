<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillStatusRequest;
use App\Models\Bill;
use App\Notifications\BillStatusUpdated;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillRequest $request)
    {
        $validated = $request->validated();

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $bill = \App\Models\Bill::create([
                'user_id' => $request->user()?->id ?? 1,
                'category_id' => $validated['category_id'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'bill_number' => $validated['bill_number'] ?? null,
                'status' => 'pending',
                'image_path' => $validated['image_path'] ?? null,
                'raw_text' => $validated['raw_text'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $bill->items()->create($item);
                }
            }

            if (!empty($validated['vendor_contact'])) {
                $bill->vendorContact()->create($validated['vendor_contact']);
            }

            return $bill->load('items', 'vendorContact');
        });

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => $bill,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Update the status of the specified bill.
     */
    public function changeStatus(UpdateBillStatusRequest $request, Bill $bill)
    {
        $validated = $request->validated();
        
        $bill->update([
            'status' => $validated['status']
        ]);

        $notification = new BillStatusUpdated($bill);

        if ($bill->user) {
            $bill->user->notify($notification);
        }

        $admins = \App\Models\User::where('role', 'admin')->get();
        \Illuminate\Support\Facades\Notification::send($admins, $notification);

        return response()->json([
            'message' => 'Bill status updated successfully',
            'data' => $bill
        ]);
    }
}
