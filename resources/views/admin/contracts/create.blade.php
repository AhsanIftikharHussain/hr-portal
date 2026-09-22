<x-layouts.admin title="Add Contract">
    <div class="flex flex-col gap-6">
        <x-page-header title="Add Employee Contract" description="Upload a confidential contract PDF and record its lifecycle details." />
        <x-card>
            <form method="POST" action="{{ route('admin.contracts.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @include('admin.contracts._form')
                <div class="flex justify-end gap-3"><a href="{{ route('admin.contracts.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Add Contract</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
