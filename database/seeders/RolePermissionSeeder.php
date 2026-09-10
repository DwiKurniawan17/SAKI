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
        $admin->syncPermissions(Permission::all());

        // ---- Default Admin/RSO User ----
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@telkom.co.id'],
            [
                'name'              => 'RSO Administrator',
                'password'          => Hash::make('Admin@12345!'),
                'is_approved'       => true,
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('admin');

        $this->command->info('Roles, permissions, dan RSO admin user berhasil dibuat.');
        $this->command->info('Login RSO: admin@telkom.co.id / Admin@12345!');
    }
}
