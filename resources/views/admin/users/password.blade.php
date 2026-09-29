<x-layouts.admin title="Reset User Password">
    <div class="flex flex-col gap-6">
        <x-page-header title="Reset User Password" :description="$user->name.' — '.$user->email" />
        <x-card>
            <form method="POST" action="{{ route('admin.users.password.update', $user) }}" class="flex flex-col gap-6" onsubmit="return confirm('Reset this user password?')">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-input label="New password" name="password" type="password" autocomplete="new-password" required />
                    <x-form-input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" required />
                </div>
                <p class="text-xs text-slate-500">Passwords must be at least 12 characters and include uppercase, lowercase, numbers, and symbols.</p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                    <x-button>Reset Password</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
