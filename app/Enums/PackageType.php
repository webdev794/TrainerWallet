<?php

namespace App\Enums;

enum PackageType: string
{
    case Session = 'session';
    case Package = 'package';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Session => 'Single session',
            self::Package => 'Session package',
            self::Monthly => 'Monthly plan',
        };
    }
}
