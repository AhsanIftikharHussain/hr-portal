<?php

namespace App\Http\Responses;

use App\Models\Role;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public static function redirectPath(Request $request): string
    {
        if ($request->user()->can('access-admin')) {
            return route('admin.dashboard');
        }

        if ($request->user()->hasRole(Role::DEPARTMENT_MANAGER)) {
            return route('manager.dashboard');
        }

        return route('profile.edit');
    }

    public function toResponse($request): Response
    {
        /** @var Request $request */
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended(self::redirectPath($request));
    }
}
