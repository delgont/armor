<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Delgont\Armor\Models\PageAccess;
use Carbon\Carbon;

class TrackPageAccess
{
    public function handle(Request $request, Closure $next, $pageName)
    {
        // Get the IP address and User-Agent of the user
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');  // Capture the User-Agent from the request

        // Check if a record exists for this page and IP
        $pageAccess = PageAccess::where('page_name', $pageName)
            ->where('ip', $ip)
            ->first();

        if ($pageAccess) {
            // If the record exists, update the count, last access time, and User-Agent
            $pageAccess->count++;
            $pageAccess->user_agent = $userAgent;  // Update the User-Agent
            $pageAccess->save();
        } else {
            // If no record exists, create a new entry
            PageAccess::create([
                'page_name' => $pageName,
                'page_url' => $request->fullUrl(), // Store the full URL of the page
                'ip' => $ip,
                'count' => 1,  // First access
                'user_agent' => $userAgent,  // Capture and store the User-Agent
            ]);
        }

        // Continue with the request
        return $next($request);
    }
}
