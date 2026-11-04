<?php


namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            app(TenantContext::class)->set($user->tenant_id);
            session(['tenant_id' => $user->tenant_id]);
        }

        return $next($request);
    }
}

