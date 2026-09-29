<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UserPasswordController extends Controller
{
    public function edit(User $user): View
    {
        Gate::authorize('resetPassword', $user);

        return view('admin.users.password', ['user' => $user]);
    }

    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('admin.users.edit', $user)->with('status', 'User password reset.');
    }
}
