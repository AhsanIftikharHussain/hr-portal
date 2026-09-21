<?php

namespace App;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnProbation = 'on-probation';
    case NoticePeriod = 'notice-period';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
    case LaidOff = 'laid-off';
    case Inactive = 'inactive';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
