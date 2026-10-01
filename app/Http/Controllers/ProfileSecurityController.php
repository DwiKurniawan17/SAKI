<?php

namespace App\Http\Controllers;

use App\Services\PHPGangstaTwoFactorProvider;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\RecoveryCode;

class ProfileSecurityController extends Controller
{
    public function index(Request $request, PHPGangstaTwoFactorProvider $provider)
    {
        $user = $request->user();

        $qrSvg = null;
        $qrUrl = null;
        $setupSecret = session('two_factor_setup_secret');
        $formattedSecret = null;

        if ($setupSecret && ! $user->has2FAEnabled()) {
            $company = config('app.name', 'PT Telkom Indonesia');
            $otpauthUrl = $provider->qrCodeUrl($company, $user->email, $setupSecret);

            // Generate clean inline SVG
            try {
                $writer = new Writer(
                    new ImageRenderer(
                        new RendererStyle(192),
                        new SvgImageBackEnd()
                    )
                );
                $svgRaw = $writer->writeString($otpauthUrl);
                // Strip XML declaration if present so it renders smoothly inline
                $qrSvg = trim(preg_replace('/<\?xml[^\?]*\?>/', '', $svgRaw));
            } catch (\Throwable $e) {
                $qrSvg = null;
            }

            // Fallback external QR code URL via PHPGangsta
            $qrUrl = $provider->getExternalQrCodeUrl($user->email, $setupSecret, $company);

            // Format secret in 4-character chunks: ABCD EFGH IJKL MNOP
            $formattedSecret = trim(chunk_split($setupSecret, 4, ' '));
        }

        // Active recovery codes if user already enabled 2FA
        $activeRecoveryCodes = null;
        if ($user->has2FAEnabled() && $user->two_factor_recovery_codes) {
            try {
                $activeRecoveryCodes = $user->recoveryCodes();
            } catch (\Throwable $e) {
                $activeRecoveryCodes = null;
            }
        }

        return view('profile.security', compact(
            'user',
            'setupSecret',
            'formattedSecret',
            'qrSvg',
            'qrUrl',
            'activeRecoveryCodes'
        ));
    }

    /**
     * Inisiasi setup 2FA menggunakan PHPGangsta Google Authenticator.
     */
    public function enable2fa(Request $request, PHPGangstaTwoFactorProvider $provider)
    {
        $user = $request->user();

        if ($user->has2FAEnabled()) {
            return redirect()->route('profile.security')->with('info', '2FA Google Authenticator sudah aktif pada akun Anda.');
        }

        // Generate 16-character secret key via PHPGangsta
        $secret = $provider->generateSecretKey();
        session(['two_factor_setup_secret' => $secret]);

        return redirect()->route('profile.security')
            ->with('status', 'Pindai kode QR dengan aplikasi Google Authenticator, lalu masukkan 6 digit kode verifikasi.');
    }

    /**
     * Konfirmasi kode OTP dari Google Authenticator dan aktifkan 2FA.
     */
    public function confirm2fa(Request $request, PHPGangstaTwoFactorProvider $provider)
    {
        $user = $request->user();
        $secret = session('two_factor_setup_secret');

        if (! $secret) {
            return redirect()->route('profile.security')->with('error', 'Sesi konfigurasi 2FA telah berakhir. Silakan klik Aktifkan kembali.');
        }

        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:8'],
        ], [
            'code.required' => 'Masukkan 6 digit kode OTP dari Google Authenticator.',
        ]);

        $code = trim(str_replace(' ', '', $request->input('code')));

        if (! $provider->verify($secret, $code)) {
            return back()->withInput()->withErrors([
                'code' => 'Kode verifikasi tidak cocok atau telah kadaluarsa. Pastikan jam smartphone Anda sinkron dan coba lagi.',
            ]);
        }

        // Generate 8 recovery codes
        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => RecoveryCode::generate())
            ->all();

        // Simpan secret dan recovery codes terenkripsi
        $user->forceFill([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes'  => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'    => now(),
        ])->save();

        session()->forget('two_factor_setup_secret');

        // Simpan recovery codes di flash session agar ditampilkan ke user
        session()->flash('newly_enabled_2fa', true);
        session()->flash('fresh_recovery_codes', $recoveryCodes);

        activity('security')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Two-Factor Authentication (Google Authenticator) berhasil diaktifkan');

        return redirect()->route('profile.security')
            ->with('status', 'Two-Factor Authentication (Google Authenticator) berhasil diaktifkan! Harap simpan kode pemulihan darurat Anda sekarang.');
    }

    /**
     * Batalkan proses setup 2FA.
     */
    public function cancel2faSetup(Request $request)
    {
        session()->forget('two_factor_setup_secret');

        return redirect()->route('profile.security');
    }

    /**
     * Nonaktifkan 2FA (memerlukan konfirmasi password saat ini).
     */
    public function disable2fa(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password_for_disable' => ['required', 'string'],
        ], [
            'password_for_disable.required' => 'Kata sandi saat ini wajib diisi untuk menonaktifkan 2FA.',
        ]);

        if (! Hash::check($request->password_for_disable, $user->password)) {
            return back()->withErrors([
                'password_for_disable' => 'Kata sandi yang Anda masukkan tidak sesuai.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes'  => null,
            'two_factor_confirmed_at'    => null,
        ])->save();

        session()->forget('two_factor_setup_secret');

        activity('security')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Two-Factor Authentication (Google Authenticator) dinonaktifkan');

        return redirect()->route('profile.security')
            ->with('status', 'Two-Factor Authentication (Google Authenticator) telah berhasil dinonaktifkan.');
    }

    /**
     * Generate ulang kode pemulihan darurat (memerlukan password saat ini).
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->has2FAEnabled()) {
            return redirect()->route('profile.security')->with('error', '2FA belum aktif.');
        }

        $request->validate([
            'password_for_recovery' => ['required', 'string'],
        ], [
            'password_for_recovery.required' => 'Kata sandi saat ini wajib diisi untuk meregenerasi kode pemulihan.',
        ]);

        if (! Hash::check($request->password_for_recovery, $user->password)) {
            return back()->withErrors([
                'password_for_recovery' => 'Kata sandi yang Anda masukkan tidak sesuai.',
            ]);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => RecoveryCode::generate())
            ->all();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        session()->flash('fresh_recovery_codes', $recoveryCodes);

        activity('security')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Kode pemulihan darurat 2FA diperbarui');

        return redirect()->route('profile.security')
            ->with('status', 'Kode pemulihan darurat 2FA baru telah berhasil dibuat. Harap simpan di tempat yang aman.');
    }
}
