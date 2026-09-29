@php($checkedRoles = old('roles', $selectedRoles ?? []))
<fieldset>
    <legend class="block text-sm font-medium text-slate-700">Roles</legend>
    <div class="mt-2 grid gap-3 sm:grid-cols-2">
        @foreach ($roleOptions as $roleSlug => $roleName)
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                <input type="checkbox" name="roles[]" value="{{ $roleSlug }}" class="rounded border-slate-300 text-slate-800 focus:ring-slate-500" @checked(in_array($roleSlug, $checkedRoles, true))>
                <span>{{ $roleName }}</span>
            </label>
        @endforeach
    </div>
    @error('roles')
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('roles.*')
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</fieldset>
