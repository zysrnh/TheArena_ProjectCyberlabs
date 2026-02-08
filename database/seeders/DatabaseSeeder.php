<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    
    public function run(): void
    {
        // Jalankan RoleSeeder terlebih dahulu
        $this->call([
            VoucherSeeder::class,
            RoleSeeder::class,
            EventNotifSeeder::class,
        ]);

        // Buat Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'super-admin@thearena.id'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('TH34REN4F1B4!'),
            ]
        );
        $admin->assignRole('super_admin');
        
        // Buat Web Admin
        $webAdmin = User::firstOrCreate(
            ['email' => 'web-admin@thearena.id'],
            [
                'name' => 'Web Admin',
                'password' => bcrypt('TH34REN4W3B!'),
            ]
        );
        $webAdmin->assignRole('web_admin');

        // Buat Editor Admin
        $editorAdmin = User::firstOrCreate(
            ['email' => 'editor-admin@thearena.id'],
            [
                'name' => 'Editor Admin',
                'password' => bcrypt('TH34REN4ED1T0R!'),
            ]
        );
        $editorAdmin->assignRole('admin_editor');

        {
        $this->call([
            MatchSeeder::class,
        ]);
    }
    
    }
}