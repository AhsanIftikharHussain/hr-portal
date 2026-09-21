<?php

namespace App\Support;

use App\Models\Employee;

class EmployeeCodeGenerator
{
    public function next(): string
    {
        $nextId = ((int) Employee::query()->withTrashed()->max('id')) + 1;

        return sprintf('EMP-%04d', $nextId);
    }
}
