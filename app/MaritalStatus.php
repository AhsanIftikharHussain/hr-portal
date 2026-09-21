<?php

namespace App;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
    case PreferNotToSay = 'prefer-not-to-say';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
