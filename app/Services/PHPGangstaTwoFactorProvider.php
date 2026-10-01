<?php

namespace App\Services;

use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use PHPGangsta_GoogleAuthenticator;

class PHPGangstaTwoFactorProvider implements TwoFactorAuthenticationProviderContract
{
    protected PHPGangsta_GoogleAuthenticator $ga;

    public function __construct()
    {
        $this->ga = new PHPGangsta_GoogleAuthenticator();
    }

    /**
     * Generate a new 16-character base32 secret key.
     */
    public function generateSecretKey(int $secretLength = 16): string
    {
        return $this->ga->createSecret($secretLength);
    }

    /**
     * Get standard otpauth URL for Google Authenticator.
     */
    public function qrCodeUrl($companyName, $companyEmail, $secret): string
    {
        $encodedCompany = rawurlencode($companyName);
        $encodedEmail = rawurlencode($companyEmail);

        return "otpauth://totp/{$encodedCompany}:{$encodedEmail}?secret={$secret}&issuer={$encodedCompany}";
    }

    /**
     * Get image QR code URL from PHPGangsta Google Authenticator.
     */
    public function getExternalQrCodeUrl(string $name, string $secret, ?string $title = null, array $params = []): string
    {
        return $this->ga->getQRCodeGoogleUrl($name, $secret, $title, $params);
    }

    /**
     * Verify the 6-digit TOTP code.
     * Discrepancy 1 allows 30 seconds clock drift backwards and forwards (RFC 6238).
     */
    public function verify($secret, $code): bool
    {
        if (empty($secret) || empty($code)) {
            return false;
        }

        // Clean any spaces or dashes user might have typed
        $cleanCode = preg_replace('/\s+/', '', (string) $code);

        return (bool) $this->ga->verifyCode($secret, $cleanCode, 1);
    }

    /**
     * Get current valid code for secret.
     */
    public function getCode(string $secret): string
    {
        return $this->ga->getCode($secret);
    }
}
