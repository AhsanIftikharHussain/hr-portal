<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $user = $guard->user();
        $hasStoredAuthentication = $request->hasSession()
            && $request->session()->has($guard->getName());
        $hasRememberCookie = $request->cookies->has($guard->getRecallerName());
        $hasInvalidAuthentication = $user === null
            && ($hasStoredAuthentication || $hasRememberCookie);

        if ($hasInvalidAuthentication || ($user !== null && ! $user->is_active)) {
            $isInactive = $user !== null && ! $user->is_active;

            $guard->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $response = redirect()->route('login');

            return $isInactive
                ? $response->withErrors(['email' => 'This account is inactive. Contact a Super Admin for access.'])
                : $response;
        }

        return $next($request);
    }
}
