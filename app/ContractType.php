<?php

namespace App;

enum ContractType: string
{
    case Permanent = 'permanent';
    case FixedTerm = 'fixed-term';
    case Probation = 'probation';
    case Internship = 'internship';
    case Consultancy = 'consultancy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Permanent',
            self::FixedTerm => 'Fixed Term',
            self::Probation => 'Probation',
            self::Internship => 'Internship',
            self::Consultancy => 'Consultancy',
            self::Other => 'Other',
        };
    }
}
