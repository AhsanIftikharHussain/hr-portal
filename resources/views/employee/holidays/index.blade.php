<x-layouts.admin title="Holiday Calendar">
    <div class="flex flex-col gap-6">
        <x-page-header title="Holiday Calendar" :description="'Active company holidays for '.$year.'.'" />
        <x-card><form method="GET" action="{{ route('employee.holidays.index') }}" class="flex flex-wrap items-end gap-3"><x-form-input label="Year" name="year" type="number" :value="$year" min="2000" max="2100" /><x-button>View Year</x-button></form></x-card>
        @if ($holidaysByMonth->isEmpty())
            <x-empty-state title="No active holidays" description="There are no active holidays for the selected year." />
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($holidaysByMonth as $month => $monthHolidays)
                    <x-card :title="$month"><div class="divide-y divide-slate-100">@foreach ($monthHolidays as $holiday)<div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"><div><p class="text-sm font-semibold text-slate-950">{{ $holiday->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $holiday->type->label() }} · {{ $holiday->day_portion->label() }}</p>@if ($holiday->description)<p class="mt-1 text-xs text-slate-600">{{ $holiday->description }}</p>@endif</div><time class="whitespace-nowrap text-sm font-medium text-slate-700" datetime="{{ $holiday->holiday_date->toDateString() }}">{{ $holiday->holiday_date->format('D, d M') }}</time></div>@endforeach</div></x-card>
                @endforeach
            </div>
        @endif
        <p class="text-xs text-slate-500">Holiday records are informational and do not alter attendance or leave duration calculations.</p>
    </div>
</x-layouts.admin>
