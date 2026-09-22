<x-card title="Leave Type Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form-input label="Name *" name="name" :value="$leaveType?->name" required />
        <x-form-input label="Stable Code *" name="code" :value="$leaveType?->code" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="annual-leave" required />
    </div>
    <p class="mt-2 text-xs text-slate-500">Use lowercase letters, numbers, and single hyphens. The code is stable and changes only when edited explicitly.</p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4"><input type="hidden" name="is_paid" value="0"><input type="checkbox" name="is_paid" value="1" class="mt-1 rounded border-slate-300" @checked(old('is_paid', $leaveType?->is_paid ?? true))><span><span class="block text-sm font-medium text-slate-800">Paid leave</span><span class="block text-xs text-slate-500">Marks this leave type as paid for reference only.</span></span></label>
        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4"><input type="hidden" name="requires_attachment" value="0"><input type="checkbox" name="requires_attachment" value="1" class="mt-1 rounded border-slate-300" @checked(old('requires_attachment', $leaveType?->requires_attachment ?? false))><span><span class="block text-sm font-medium text-slate-800">Supporting evidence normally required</span><span class="block text-xs text-slate-500">Informational only; uploads are not included in Phase 3.</span></span></label>
    </div>
</x-card>
