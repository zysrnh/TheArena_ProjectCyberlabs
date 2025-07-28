<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $webAdminRole = Role::firstOrCreate(['name' => 'web_admin']);
        $adminViewRole = Role::firstOrCreate(['name' => 'admin_view']);
        $adminEditorRole = Role::firstOrCreate(['name' => 'admin_editor']);

        $adminViewRole->givePermissionTo([
            'view_any_registration',
            'view_registration',
        ]);

        $adminEditorRole->givePermissionTo([
            'view_any_registration',
            'view_registration',
            'create_registration',
            'update_registration',
        ]);

        $webAdminRole->givePermissionTo([
            'view_any_registration',
            'view_registration',
            'create_registration',
            'update_registration',
            'delete_registration',
            'delete_any_registration',
            'force_delete_registration',
            'force_delete_any_registration',
            'restore_registration',
            'restore_any_registration',
            'replicate_registration',
            'reorder_registration',
        ]);
    }
}
