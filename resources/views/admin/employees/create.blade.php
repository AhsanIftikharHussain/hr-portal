<x-layouts.admin title="Add Employee">
    <div class="flex flex-col gap-6">
        <x-page-header title="Add Employee" description="Create an HR record without automatically creating portal access." />
        <form method="POST" action="{{ route('admin.employees.store') }}">
            @csrf
            @include('admin.employees._form', ['submitLabel' => 'Create Employee', 'cancelUrl' => route('admin.employees.index')])
        </form>
    </div>
</x-layouts.admin>
