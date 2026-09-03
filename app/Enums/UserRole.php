<?php

namespace App\Enums;

enum UserRole: string
{
    case Trainer = 'trainer';
    case Client = 'client';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Trainer => 'Trainer',
            self::Client => 'Client',
            self::Admin => 'Admin',
        };
    }
}
