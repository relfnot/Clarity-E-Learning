<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, mixed>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $value = $data[$field] ?? null;
            $isNullable = in_array('nullable', $ruleList, true);

            if (is_string($value) && !str_contains(strtolower($field), 'password')) {
                $value = trim($value);
            }

            $isEmpty = $value === null || $value === '' || $value === [];
            if ($isNullable && $isEmpty) {
                $validated[$field] = null;
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                if ($rule === 'required') {
                    if ($isEmpty) {
                        $errors[$field][] = "Field {$field} wajib diisi.";
                    }
                    continue;
                }

                if ($isEmpty) {
                    continue;
                }

                if ($rule === 'string' && !is_string($value)) {
                    $errors[$field][] = "Field {$field} harus berupa teks.";
                } elseif ($rule === 'email' && (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false)) {
                    $errors[$field][] = "Field {$field} harus berupa alamat email yang valid.";
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $errors[$field][] = "Field {$field} minimal {$min} karakter.";
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $errors[$field][] = "Field {$field} maksimal {$max} karakter.";
                    }
                } elseif (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = "Field {$field} harus salah satu dari: " . implode(', ', $allowed) . '.';
                    }
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Validasi input gagal. Periksa kembali data yang Anda kirim.',
                422,
                $errors
            );
        }

        return $validated;
    }
}
