<?php

namespace App\Enums;

enum CategoryType: string
{
    case Intention = 'intention';
    case Mass = 'mass';

    public function label(): string
    {
        return match ($this) {
            self::Intention => 'Intention Category',
            self::Mass => 'Mass Category',
        };
    }
}
