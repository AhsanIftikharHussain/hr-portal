<?php

namespace App\Http\Controllers\Admin;

use App\HolidayDayPortion;
use App\HolidayType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Holiday::class);
        $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'type' => ['nullable', Rule::enum(HolidayType::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'record_status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $year = $request->integer('year') ?: now()->year;

        $holidays = Holiday::query()
            ->whereYear('holiday_date', $year)
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')))
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search')->trim()->toString().'%'))
            ->when($request->string('record_status')->toString() === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->string('record_status')->toString() === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.holidays.index', [
            'holidays' => $holidays,
            'year' => $year,
            'types' => HolidayType::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Holiday::class);

        return view('admin.holidays.create', $this->formData() + ['holiday' => new Holiday]);
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::query()->create([
            ...$request->safe()->only(['name', 'holiday_date', 'type', 'day_portion', 'description']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.holidays.index', ['year' => $request->date('holiday_date')->year])
            ->with('status', 'Holiday created.');
    }

    public function edit(Holiday $holiday): View
    {
        Gate::authorize('update', $holiday);

        return view('admin.holidays.edit', $this->formData() + ['holiday' => $holiday]);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->safe()->only(['name', 'holiday_date', 'type', 'day_portion', 'description']));

        return redirect()->route('admin.holidays.index', ['year' => $holiday->holiday_date->year])
            ->with('status', 'Holiday updated.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'types' => HolidayType::cases(),
            'dayPortions' => HolidayDayPortion::cases(),
        ];
    }
}
