<?php

namespace App\Actions;

use App\DTOs\AiParsedBillDTO;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreBillAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, int $categoryId, string $imagePath, AiParsedBillDTO $aiDTO)
    {
        return DB::transaction(function () use ($user, $categoryId, $imagePath, $aiDTO) {
            $monthYear = now()->format('Y-m');

            // Check or create pivot
            $pivot = CategoryMonthlyPivot::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                    'month_year' => $monthYear,
                ],
                [
                    'bill_count' => 0,
                    'total_spent' => 0,
                    'last_updated_at' => now(),
                ]
            );

            // Create bill
            $bill = Bill::create(array_merge($aiDTO->bill, [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'category_monthly_pivot_id' => $pivot->id,
                'status' => 'pending',
                'file_path' => $imagePath,
            ]));

            // Bulk insert items
            if (! empty($aiDTO->billItems)) {
                $now = Carbon::now();
                $items = array_map(fn ($item) => array_merge($item, [
                    'bill_id' => $bill->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]), $aiDTO->billItems);

                BillItem::query()->insert($items);
            }

            // Create vendor contact
            if (! empty($aiDTO->vendorContact)) {
                $bill->vendorContact()->create($aiDTO->vendorContact);
            }

            // Update pivot stats
            $pivot->increment('bill_count');
            $pivot->increment('total_spent', $aiDTO->amount ?? 0);
            $pivot->update(['last_updated_at' => now()]);

            return $bill->load('items', 'vendorContact');
        });
    }
}
