<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;




class AllowSchedulingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->canPermission('schedule.generate')) {
            return $next($request);
        }

        abort(403, 'Scheduling requires authentication and schedule.generate permission outside local/testing.');
    }
}
