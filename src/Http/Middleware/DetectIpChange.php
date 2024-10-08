<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class DetectIpChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::check()){
            $user = Auth::user();
            $currentIp = $request->ip();

            // If the IP address has changed
            if ($user->last_ip && $user->last_ip !== $currentIp) {
                Auth::logout();
                return redirect('/login')->withErrors(['message' => 'Your IP address has changed. Please log in again.']);
            }

            // Update the user's last IP address
            $user->last_ip = $currentIp;
            $user->save();
        }
        return $next($request);
    }
}
