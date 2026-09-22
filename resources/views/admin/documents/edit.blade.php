<x-layouts.admin title="Edit Employee Document">
    <div class="flex flex-col gap-6">
        <x-page-header title="Edit Employee Document" :description="$employee->employee_code.' · '.$employee->full_name" />
        <x-card>
            <form method="POST" action="{{ route('admin.employee-documents.update', [$employee, $document]) }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                @include('admin.documents._form')
                <div class="flex justify-end gap-3"><a href="{{ route('admin.employees.show', $employee) }}#documents" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Save Changes</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
