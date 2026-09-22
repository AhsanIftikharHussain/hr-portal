<?php

namespace App\Http\Controllers\Admin;

use App\ContractStatus;
use App\ContractType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeContractRequest;
use App\Http\Requests\UpdateEmployeeContractRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Support\PrivateEmployeeFileStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmployeeContractController extends Controller
{
    public function __construct(private PrivateEmployeeFileStorage $privateFiles) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EmployeeContract::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'status' => ['nullable', Rule::enum(ContractStatus::class)],
            'contract_type' => ['nullable', Rule::enum(ContractType::class)],
            'expiry_window' => ['nullable', 'integer', Rule::in([30, 60, 90])],
        ]);

        $contracts = EmployeeContract::query()
            ->with(['employee.department:id,name', 'uploader:id,name'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->whereHas('employee', function (Builder $employeeQuery) use ($search): void {
                    $employeeQuery->withTrashed()->where(function (Builder $nameQuery) use ($search): void {
                        $nameQuery->where('full_name', 'like', $search)
                            ->orWhere('employee_code', 'like', $search);
                    });
                });
            })
            ->when($request->integer('department_id'), fn (Builder $query, int $departmentId) => $query->whereHas(
                'employee',
                fn (Builder $employeeQuery) => $employeeQuery->withTrashed()->where('department_id', $departmentId),
            ))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('contract_type'), fn (Builder $query) => $query->where('contract_type', $request->string('contract_type')))
            ->when($request->integer('expiry_window'), function (Builder $query, int $days): void {
                $query->whereIn('status', ContractStatus::expiringStatuses())
                    ->whereBetween('end_date', [today(), today()->addDays($days)])
                    ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->whereNull('employees.deleted_at'));
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $contracts->getCollection()->each(function (EmployeeContract $contract): void {
            $contract->setAttribute(
                'file_available',
                $this->privateFiles->exists($contract->file_path, $contract->employee, 'contracts'),
            );
        });

        return view('admin.contracts.index', [
            'contracts' => $contracts,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'contractTypes' => ContractType::cases(),
            'contractStatuses' => ContractStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', EmployeeContract::class);
        $request->validate([
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
        ]);

        return view('admin.contracts.create', $this->formData() + [
            'contract' => new EmployeeContract(['employee_id' => $request->integer('employee_id') ?: null]),
        ]);
    }

    public function store(StoreEmployeeContractRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $metadata = $this->privateFiles->store($request->file('contract_file'), $employee, 'contracts');

        try {
            $contract = EmployeeContract::query()->create([
                ...$request->safe()->only([
                    'employee_id', 'contract_type', 'start_date', 'end_date', 'status', 'notice_period', 'notes',
                ]),
                ...$metadata,
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            $this->privateFiles->delete($metadata['file_path'], $employee, 'contracts');

            throw $exception;
        }

        return redirect()->route('admin.contracts.index')
            ->with('status', "Contract for {$contract->employee->full_name} added.");
    }

    public function edit(EmployeeContract $contract): View
    {
        Gate::authorize('update', $contract);
        $contract->load('employee');

        return view('admin.contracts.edit', $this->formData() + ['contract' => $contract]);
    }

    public function update(UpdateEmployeeContractRequest $request, EmployeeContract $contract): RedirectResponse
    {
        $contract->load('employee');
        $oldPath = $contract->file_path;
        $metadata = [];

        if ($request->hasFile('contract_file')) {
            $metadata = $this->privateFiles->store($request->file('contract_file'), $contract->employee, 'contracts');
        }

        try {
            $contract->update([
                ...$request->safe()->only([
                    'contract_type', 'start_date', 'end_date', 'status', 'notice_period', 'notes',
                ]),
                ...$metadata,
                ...($metadata === [] ? [] : ['uploaded_by' => $request->user()->id]),
            ]);
        } catch (Throwable $exception) {
            if ($metadata !== []) {
                $this->privateFiles->delete($metadata['file_path'], $contract->employee, 'contracts');
            }

            throw $exception;
        }

        if ($metadata !== []) {
            $this->privateFiles->delete($oldPath, $contract->employee, 'contracts');
        }

        return redirect()->route('admin.contracts.index')->with('status', 'Contract updated.');
    }

    public function download(EmployeeContract $contract): StreamedResponse
    {
        Gate::authorize('download', $contract);
        $contract->load('employee');

        return $this->privateFiles->download(
            $contract->file_path,
            $contract->original_filename,
            $contract->mime_type,
            $contract->employee,
            'contracts',
        );
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'employees' => Employee::query()->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'contractTypes' => ContractType::cases(),
            'contractStatuses' => ContractStatus::cases(),
        ];
    }
}
