<x-layouts.guest :title="'Choose a new password — '.config('app.name')">
    <h2 class="text-xl font-semibold">Choose a new password</h2>
    <p class="mt-1 text-sm text-slate-600">Set a secure password for your account.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form-input label="Email address" name="email" type="email" :value="$request->email" autocomplete="email" required />
        <x-form-input label="New password" name="password" type="password" autocomplete="new-password" required />
        <x-form-input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" required />
        <x-button class="w-full">Reset password</x-button>
    </form>
</x-layouts.guest>
