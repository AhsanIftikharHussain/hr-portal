<x-layouts.admin title="Record Attendance">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <x-page-header title="Record Attendance" description="Create one explicit daily attendance record for an employee." />
        <form method="POST" action="{{ route('admin.attendance.store') }}">@csrf @include('admin.attendance._form')<div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.attendance.index', ['date' => $attendanceRecord->attendance_date?->format('Y-m-d')]) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Record Attendance</x-button></div></form>
    </div>
</x-layouts.admin>
