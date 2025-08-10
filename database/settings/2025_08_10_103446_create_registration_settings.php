<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('registration.regular_limit', -1);
        $this->migrator->add('registration.vip_limit', -1);
        $this->migrator->add('registration.pers_limit', -1);
    }
};
