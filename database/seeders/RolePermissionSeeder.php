<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- Permissions ----
        $permissions = [
            'simulasi.create',
            'simulasi.view-own',
            'simulasi.view-all',
            'simulasi.fill-revenue',     // Pihak B fields
            'simulasi.fill-capex',       // Pihak A fields
            'simulasi.download',
            'simulasi.delete',
            'simulasi.review',           // RSO: approve/reject
            'admin.manage-users',
            'admin.view-audit-log',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ---- Roles ----
        // Marketing (Pihak B) — mengisi data revenue/tarif
        $salesMarketing = Role::firstOrCreate(['name' => 'sales_marketing']);
        $salesMarketing->syncPermissions([
            'simulasi.create',
            'simulasi.view-own',
            'simulasi.fill-revenue',
            'simulasi.download',
            'simulasi.delete',
        ]);

        // Engineering Delivery (Pihak A) — mengisi data CAPEX
        $engineeringCapex = Role::firstOrCreate(['name' => 'engineering_capex']);
        $engineeringCapex->syncPermissions([
            'simulasi.create',
            'simulasi.view-own',
            'simulasi.fill-capex',
            'simulasi.download',
        ]);

        // RSO (Admin) — review, approve/reject, cetak Excel, kelola user
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = Permission::where('name', '!=', 'simulasi.create')->get();
        $admin->syncPermissions($adminPermissions);

        // ---- 1. Default Admin / RSO User ----
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@telkom.co.id'],
            [
                'name'              => 'RSO Administrator',
                'password'          => Hash::make('password'),
                'is_approved'       => true,
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['admin']);

        // ---- 2. Default Engineer Development (ED) User ----
        $edUser = User::updateOrCreate(
            ['email' => 'ed@telkom.co.id'],
            [
                'name'              => 'Engineer Development (ED)',
                'password'          => Hash::make('password'),
                'is_approved'       => true,
                'email_verified_at' => now(),
            ]
        );
        $edUser->syncRoles(['engineering_capex']);

        // ---- 3. Default Sales Marketing User ----
        $marketingUser = User::updateOrCreate(
            ['email' => 'marketing@telkom.co.id'],
            [
                'name'              => 'Sales Marketing',
                'password'          => Hash::make('password'),
                'is_approved'       => true,
                'email_verified_at' => now(),
            ]
        );
        $marketingUser->syncRoles(['sales_marketing']);

        $this->command->info('Roles, permissions, dan user demo (Admin, ED, Marketing) berhasil disiapkan.');
        $this->command->info('Password seluruh user demo: password');
    }
}
