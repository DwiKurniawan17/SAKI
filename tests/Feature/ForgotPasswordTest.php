<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Lupa Kata Sandi?');
        $response->assertSee('Alamat Email Terdaftar');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'staff@telkom.co.id',
            'is_approved' => true,
        ]);
        $user->assignRole('sales_marketing');

        $response = $this->post('/forgot-password', [
            'email' => 'staff@telkom.co.id',
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@telkom.co.id',
            'is_approved' => true,
        ]);
        $user->assignRole('sales_marketing');

        $token = Password::createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Kata Sandi');
        $response->assertSee('Kata Sandi Baru');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@telkom.co.id',
            'password' => Hash::make('old_password123'),
            'is_approved' => true,
        ]);
        $user->assignRole('sales_marketing');

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'staff@telkom.co.id',
            'password' => 'new_secret_password123',
            'password_confirmation' => 'new_secret_password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('new_secret_password123', $user->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@telkom.co.id',
            'password' => Hash::make('old_password123'),
            'is_approved' => true,
        ]);
        $user->assignRole('sales_marketing');

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => 'staff@telkom.co.id',
            'password' => 'new_secret_password123',
            'password_confirmation' => 'new_secret_password123',
        ]);

        $response->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertTrue(Hash::check('old_password123', $user->password));
    }
}
