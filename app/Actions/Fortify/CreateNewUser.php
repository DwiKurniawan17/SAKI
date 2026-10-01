<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            // Admin TIDAK BISA dipilih sendiri — hanya Pihak A (ED) / Pihak B (Marketing)
            'role' => ['required', 'string', 'in:engineering_capex,sales_marketing'],
        ], [
            'role.in' => 'Role yang dipilih tidak valid. Pilih ED atau Marketing.',
            'password.min' => 'Password minimal 8 karakter.',
        ])->validate();

        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'phone'    => $input['phone'] ?? null,
            'password' => Hash::make($input['password']),
            // Email harus diverifikasi sendiri via link, bukan di-bypass
            // 'email_verified_at' => now(),  // DIHAPUS
            // Akun baru WAJIB menunggu persetujuan Admin/RSO sebelum bisa login
            'is_approved' => false,
        ]);

        $user->assignRole($input['role']);

        // Log registration
        activity('auth')
            ->causedBy($user)
            ->withProperties(['role' => $input['role']])
            ->log('User baru mendaftar');

        return $user;
    }
}
