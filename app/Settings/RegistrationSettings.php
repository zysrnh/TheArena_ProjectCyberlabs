<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RegistrationSettings extends Settings
{
    public int $regular_limit;
    public int $vip_limit;
    public int $pers_limit;

    public static function group(): string
    {
        return 'registration';
    }
}