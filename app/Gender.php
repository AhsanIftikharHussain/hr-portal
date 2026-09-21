<?php

namespace App;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case NonBinary = 'non-binary';
    case PreferNotToSay = 'prefer-not-to-say';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
