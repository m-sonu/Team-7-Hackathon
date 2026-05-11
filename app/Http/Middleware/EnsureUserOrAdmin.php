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
        $routeUserId = $request->route('id');

        // allow only own data
        if ($authUser && (int) $authUser->id === (int) $routeUserId) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized access'
        ], 403);
    }
}
