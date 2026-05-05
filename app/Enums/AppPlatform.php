<?php

namespace App\Enums;

enum AppPlatform: string
{
    case Android = 'android';
    case Ios = 'ios';

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iOS',
        };
    }
}
