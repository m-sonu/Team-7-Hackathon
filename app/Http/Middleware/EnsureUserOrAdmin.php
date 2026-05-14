<?php

// app/Http/Middleware/EnsureUserOrAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use App\Models\BillUploadBatch;

class EnsureUserOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $authUser = Auth::user();

        // allow admin
        if ($authUser && $authUser->role === UserRole::ADMIN) {
            return $next($request);
        }
        if ($request->route('user')) {
            $userId = (int) $request->route('user');

            if ($authUser->id === $userId) {
                return $next($request);
            }
        }
        if ($request->route('id')) {
             $billId = $request->route('id');
            $billUserId = BillUploadBatch::where('id', $billId)->value('user_id');

            if ($authUser->id === (int) $billUserId) {
                return $next($request);
            }
        }
        return response()->json([
            'success'=>false,
            'message' => 'Unauthorized access'
        ], 403);
    }
}
