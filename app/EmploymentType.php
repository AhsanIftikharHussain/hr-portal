<?php

namespace App;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Probation = 'probation';
    case Contract = 'contract';
    case PartTime = 'part-time';
    case Intern = 'intern';
    case Consultant = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::PartTime => 'Part-Time',
            default => str($this->value)->headline()->toString(),
        };
    }
}
