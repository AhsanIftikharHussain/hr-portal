<?php

namespace App;

enum PolicyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => true,
            self::Published => in_array($status, [self::Published, self::Archived], true),
            self::Archived => $status === self::Archived,
        };
    }
}
