<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserRole;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
          if ($request->user() && $request->user()->role === UserRole::EMPLOYEE) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Employee access required.'], 403);
    }
}
