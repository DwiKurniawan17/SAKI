<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PHPGangstaTwoFactorProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    public function test_phpgangsta_provider_generates_and_verifies_code(): void
    {
        $provider = new PHPGangstaTwoFactorProvider();

        $secret = $provider->generateSecretKey();
        $this->assertNotEmpty($secret);
        $this->assertEquals(16, strlen($secret));

        $code = $provider->getCode($secret);
        $this->assertNotEmpty($code);
        $this->assertEquals(6, strlen($code));

        $isValid = $provider->verify($secret, $code);
        $this->assertTrue($isValid);

        $isInvalid = $provider->verify($secret, '000000');
        // If 000000 doesn't match current code
        if ($code !== '000000') {
            $this->assertFalse($isInvalid);
        }
    }
}
