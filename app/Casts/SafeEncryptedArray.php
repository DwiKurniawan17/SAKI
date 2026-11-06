<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SafeEncryptedArray implements CastsAttributes
{
    /**
     * Cast the given value from database.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        try {
            $decrypted = Crypt::decrypt($value);
            return is_array($decrypted) ? $decrypted : (json_decode($decrypted, true) ?: []);
        } catch (DecryptException $e) {
            // Cek apakah data tersimpan sebagai JSON biasa (sebelum enkripsi diaktifkan)
            $plain = json_decode((string)$value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($plain)) {
                return $plain;
            }

            Log::warning("SafeEncryptedArray: Gagal mendekripsi {$key} pada model " . get_class($model) . " ID: " . ($model->id ?? 'unknown') . ". Mengembalikan array kosong agar sistem tidak crash.");
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::encrypt($value);
    }
}
