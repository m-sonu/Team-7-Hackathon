<?php

// app/Http/Middleware/EnsureUserOrAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class EnsureUserOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $authUser = Auth::user();

        // allow admin
        if ($authUser && $authUser->role === UserRole::ADMIN) {
            return $next($request);
        }

        // check user id from route
        $routeBillId = $request->route('id');
        $billUserId = BillUploadBatch::where('id', $routeBillId)->value('user_id');

        // allow only own data
        if ($authUser && (int) $authUser->id === (int) $billUserId) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized access'
        ], 403);
    }
}
