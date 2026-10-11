<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * The password rule set in Settings → Security, shared by every form that
 * sets a password: creating or editing a user, and changing your own.
 */
final class PasswordPolicy
{
    public static function rule(): Password
    {
        $rule = Password::min(self::minLength());

        if (config('hrpro.security.password_letters_numbers', true)) {
            $rule->letters()->numbers();
        }
        if (config('hrpro.security.password_mixed_case', false)) {
            $rule->mixedCase();
        }
        if (config('hrpro.security.password_symbols', false)) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * Thai messages for the rule, in the shape a FormRequest's messages() takes.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย '.self::minLength().' ตัวอักษร',
            'password.letters' => 'รหัสผ่านต้องมีตัวอักษรอย่างน้อย 1 ตัว',
            'password.numbers' => 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว',
            'password.mixed' => 'รหัสผ่านต้องมีทั้งตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก',
            'password.symbols' => 'รหัสผ่านต้องมีสัญลักษณ์อย่างน้อย 1 ตัว',
        ];
    }

    /** One line describing the current rule, for the hint under a password field. */
    public static function hint(): string
    {
        $parts = ['อย่างน้อย '.self::minLength().' ตัวอักษร'];

        if (config('hrpro.security.password_letters_numbers', true)) {
            $parts[] = 'มีทั้งตัวอักษรและตัวเลข';
        }
        if (config('hrpro.security.password_mixed_case', false)) {
            $parts[] = 'มีตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก';
        }
        if (config('hrpro.security.password_symbols', false)) {
            $parts[] = 'มีสัญลักษณ์';
        }

        return implode(' · ', $parts);
    }

    private static function minLength(): int
    {
        return max(6, (int) config('hrpro.security.password_min_length', 8));
    }
}
