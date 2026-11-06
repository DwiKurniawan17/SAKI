<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailCodeController extends Controller
{
    /**
     * Verifikasi kode 6-digit OTP email.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Masukkan 6 digit kode verifikasi.',
            'code.size'     => 'Kode verifikasi harus terdiri dari 6 digit.',
        ]);

        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (!$user->verifyEmailCode($request->code)) {
            return back()->withErrors([
                'code' => 'Kode verifikasi tidak valid atau telah kadaluarsa (berlaku 15 menit). Silakan coba lagi atau kirim ulang kode baru.',
            ]);
        }

        // Catat di activity log
        activity('auth')
            ->causedBy($user)
            ->log('Email berhasil diverifikasi dengan kode OTP');

        // Jika akun belum disetujui Admin/RSO (is_approved = false), keluarkan sesi dan beri tahu user
        if (!$user->is_approved) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Email Anda berhasil diverifikasi! Pendaftaran Anda kini sedang menunggu persetujuan Administrator/RSO sebelum akun dapat digunakan.');
        }

        return redirect()->route('dashboard')->with('status', 'Email Anda berhasil diverifikasi.');
    }
}
