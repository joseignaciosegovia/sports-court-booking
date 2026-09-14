<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Rutas consideradas parte de la intranet (gestión/administración).
     */
    public static function isIntranetRequest(Request $request): bool
    {
        return $request->is('gestion/*') || $request->is('admin/*');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            if (self::isIntranetRequest($request)) {
                return redirect()->guest(route('intranet.login'));
            }
            return redirect()->guest(route('login'));
        }

        if (!in_array(Auth::user()->role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}