<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $destination = $request->user()->can('access-admin')
            ? route('admin.dashboard')
            : route('profile.edit');

        return redirect()->intended($destination);
    }
}
