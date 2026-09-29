<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProfilePasswordController extends Controller
{
    public function update(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Password changed.');
    }
}
