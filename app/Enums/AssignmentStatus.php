<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Generated = 'generated';
    case Dispatched = 'dispatched';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Complete = 'complete';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Generated => 'Generated',
            self::Dispatched => 'Dispatched',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Complete => 'Complete',
            self::Error => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Generated => 'blue',
            self::Dispatched => 'amber',
            self::Submitted => 'purple',
            self::UnderReview => 'emerald',
            self::Complete => 'green',
            self::Error => 'red',
        };
    }
}
