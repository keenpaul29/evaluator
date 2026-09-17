<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case Submitted = 'submitted';
    case Analyzing = 'analyzing';
    case Evaluated = 'evaluated';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Analyzing => 'Analyzing',
            self::Evaluated => 'Evaluated',
            self::Shortlisted => 'Shortlisted',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'blue',
            self::Analyzing => 'yellow',
            self::Evaluated => 'green',
            self::Shortlisted => 'emerald',
            self::Rejected => 'red',
        };
    }
}
