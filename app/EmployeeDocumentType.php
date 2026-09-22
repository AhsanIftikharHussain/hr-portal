<?php

namespace App;

enum EmployeeDocumentType: string
{
    case NicCopy = 'nic-copy';
    case CvResume = 'cv-resume';
    case QualificationDegree = 'qualification-degree';
    case EmploymentLetter = 'employment-letter';
    case ConfirmationLetter = 'confirmation-letter';
    case WarningLetter = 'warning-letter';
    case ResignationLetter = 'resignation-letter';
    case TerminationLetter = 'termination-letter';
    case ExperienceLetter = 'experience-letter';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NicCopy => 'NIC Copy',
            self::CvResume => 'CV / Resume',
            self::QualificationDegree => 'Qualification / Degree',
            self::EmploymentLetter => 'Employment Letter',
            self::ConfirmationLetter => 'Confirmation Letter',
            self::WarningLetter => 'Warning Letter',
            self::ResignationLetter => 'Resignation Letter',
            self::TerminationLetter => 'Termination Letter',
            self::ExperienceLetter => 'Experience Letter',
            self::Other => 'Other',
        };
    }
}
