<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Check if the account is suspended
            if ($user->suspended) {
                // If the suspended_till field is set, compare with the current date
                if ($user->suspended_till && Carbon::now()->lt($user->suspended_till)) {
                    // Block access if still suspended
                    return response()->json([
                        'message' => 'Your account is suspended until ' . $user->suspended_till,
                        'success' => false
                    ], 403); // 403 Forbidden status code
                }
            }
        }

        return $next($request);
    }
}
