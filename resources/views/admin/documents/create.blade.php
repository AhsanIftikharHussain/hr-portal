<x-layouts.admin title="Upload Employee Document">
    <div class="flex flex-col gap-6">
        <x-page-header title="Upload Employee Document" :description="$employee->employee_code.' · '.$employee->full_name" />
        <x-card>
            <form method="POST" action="{{ route('admin.employee-documents.store', $employee) }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @include('admin.documents._form')
                <div class="flex justify-end gap-3"><a href="{{ route('admin.employees.show', $employee) }}#documents" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Upload Document</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
