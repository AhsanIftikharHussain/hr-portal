<x-layouts.admin title="Edit User">
    <div class="flex flex-col gap-6">
        <x-page-header title="Edit User" :description="$user->email">
            <x-slot:actions>
                <a href="{{ route('admin.users.password.edit', $user) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset Password</a>
            </x-slot:actions>
        </x-page-header>
        <x-card>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-col gap-6" onsubmit="return confirm('Save these account and access changes?')">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-input label="Name" name="name" :value="$user->name" maxlength="255" required autofocus />
                    <x-form-input label="Email address" name="email" type="email" :value="$user->email" autocomplete="email" maxlength="255" required />
                </div>

                @if ($isSuperAdminAccount)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Super Admin role and active status are protected from changes on this screen.
                    </div>
                    <input type="hidden" name="is_active" value="1">
                @else
                    @include('admin.users._roles')
                    <x-form-select label="Account status" name="is_active" :options="['1' => 'Active', '0' => 'Inactive']" :value="$user->is_active ? '1' : '0'" required />
                    <p class="text-xs text-slate-500">Inactive accounts retain their records and ownership history but cannot sign in.</p>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                    <x-button>Save Changes</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
