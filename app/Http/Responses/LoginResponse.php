<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public static function redirectPath(Request $request): string
    {
        return $request->user()->can('access-admin')
            ? route('admin.dashboard')
            : route('profile.edit');
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
