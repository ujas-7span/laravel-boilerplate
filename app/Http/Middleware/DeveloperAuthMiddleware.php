<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeveloperAuthMiddleware
{
    /**
     * Handle an incoming request for developer portal and developer tools.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('developer.enabled', true)) {
            abort(404);
        }

        if (! (bool) session()->get('developer_authenticated', false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated from developer portal.',
                ], 401);
            }

            return redirect()->guest(route('developer.login'));
        }

        return $next($request);
    }
}
