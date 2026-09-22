<?php

namespace App;

enum AttendanceSource: string
{
    case EmployeePortal = 'employee-portal';
    case HrManual = 'hr-manual';
    case HrCorrection = 'hr-correction';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::EmployeePortal => 'Employee Portal',
            self::HrManual => 'HR Manual',
            self::HrCorrection => 'HR Correction',
            self::System => 'System',
        };
    }
}
