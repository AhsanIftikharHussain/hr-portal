<x-layouts.admin :title="'Edit '.$employee->full_name">
    <div class="flex flex-col gap-6">
        <x-page-header :title="'Edit '.$employee->full_name" description="Update the employee record and employment details." />
        <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
            @csrf @method('PUT')
            @include('admin.employees._form', ['submitLabel' => 'Save Changes', 'cancelUrl' => route('admin.employees.show', $employee)])
        </form>
    </div>
</x-layouts.admin>
