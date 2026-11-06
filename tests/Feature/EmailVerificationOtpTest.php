<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_registration_generates_otp_code_and_redirects_to_verify_notice(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name'                  => 'Test Engineer',
            'email'                 => 'test_engineer@telkom.co.id',
            'phone'                 => '081234567890',
            'role'                  => 'engineering_capex',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'test_engineer@telkom.co.id')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->verification_code);
        $this->assertEquals(6, strlen($user->verification_code));
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo(
            $user,
            EmailVerificationOtpNotification::class,
            function ($notification) use ($user) {
                return $notification->code === $user->verification_code;
            }
        );
    }

    public function test_unverified_unapproved_user_can_access_verify_email_page(): void
    {
        $user = User::create([
            'name'        => 'Unapproved User',
            'email'       => 'unapproved@telkom.co.id',
            'password'    => bcrypt('password123'),
            'is_approved' => false,
        ]);
        $user->assignRole('sales_marketing');
        $user->generateEmailVerificationCode();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Email Anda');
        $response->assertSee('unapproved@telkom.co.id');
    }

    public function test_submitting_invalid_otp_code_fails(): void
    {
        $user = User::create([
            'name'        => 'Test User OTP',
            'email'       => 'test_otp_fail@telkom.co.id',
            'password'    => bcrypt('password123'),
            'is_approved' => false,
        ]);
        $user->assignRole('sales_marketing');
        $user->generateEmailVerificationCode();

        $response = $this->actingAs($user)->post(route('verification.verify-code'), [
            'code' => '000000', // code salah
        ]);

        $response->assertSessionHasErrors('code');
        $user->refresh();
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_submitting_valid_otp_code_verifies_email(): void
    {
        $user = User::create([
            'name'        => 'Test User Success',
            'email'       => 'test_otp_success@telkom.co.id',
            'password'    => bcrypt('password123'),
            'is_approved' => false,
        ]);
        $user->assignRole('sales_marketing');
        $code = $user->generateEmailVerificationCode();

        $response = $this->actingAs($user)->post(route('verification.verify-code'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->verification_code);
    }

    public function test_resending_otp_notification_generates_new_code(): void
    {
        Notification::fake();

        $user = User::create([
            'name'        => 'Resend User',
            'email'       => 'resend@telkom.co.id',
            'password'    => bcrypt('password123'),
            'is_approved' => false,
        ]);
        $user->assignRole('sales_marketing');

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertStatus(302);
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
    }
}
