<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePortalSessionHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (trim((string) $request->header('X-Portal-Session', '')) === '') {
            return response()->json([
                'message' => 'Sesion B2B requerida.',
                'meta' => ['source' => 'fastevo-public-b2b', 'session_required' => true],
            ], 401);
        }

        return $next($request);
    }
}
