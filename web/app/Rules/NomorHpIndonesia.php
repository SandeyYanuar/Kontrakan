<?php

namespace App\Rules;

use App\Support\NomorHp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nomor HP harus diawali 08, 62, atau +62. Normalisasi ke format 62 dilakukan sebelum validasi.
 */
class NomorHpIndonesia implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || NomorHp::normalisasi($value) === null) {
            $fail('Nomor HP harus diawali 08 atau 62.');
        }
    }
}
