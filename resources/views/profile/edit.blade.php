<x-layouts.admin title="My Profile">
    <div class="flex flex-col gap-6">
        <x-page-header title="My Profile" description="Manage your account details and password." />

        <x-card title="Profile details">
            <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-input label="Name" name="name" :value="$user->name" maxlength="255" required autocomplete="name" />
                    <x-form-input label="Email address" name="email" type="email" :value="$user->email" maxlength="255" required autocomplete="email" />
                </div>
                <div class="flex justify-end"><x-button>Save Profile</x-button></div>
            </form>
        </x-card>

        <x-card title="Change password">
            <form method="POST" action="{{ route('profile.password.update') }}" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form-input label="Current password" name="current_password" type="password" autocomplete="current-password" required />
                    <x-form-input label="New password" name="password" type="password" autocomplete="new-password" required />
                    <x-form-input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" required />
                </div>
                <p class="text-xs text-slate-500">Use at least 12 characters with uppercase, lowercase, numbers, and symbols.</p>
                <div class="flex justify-end"><x-button>Change Password</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
