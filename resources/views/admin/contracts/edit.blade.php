<x-layouts.admin title="Edit Contract">
    <div class="flex flex-col gap-6">
        <x-page-header title="Edit Contract" :description="$contract->employee->full_name.' · '.$contract->contract_type->label()" />
        <x-card>
            <form method="POST" action="{{ route('admin.contracts.update', $contract) }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                @include('admin.contracts._form')
                <div class="flex justify-end gap-3"><a href="{{ route('admin.contracts.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Save Changes</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
