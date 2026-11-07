<?php
declare(strict_types=1);

namespace App;

/**
 * Small rule-based validator. Returns field => message.
 * Kept intentionally simple — the app does not need a framework.
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules field => "required|min:3|max:160"
     * @return array<string, string>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = trim((string) ($data[$field] ?? ''));
            $label = self::label($field);

            foreach (explode('|', $ruleString) as $rule) {
                if ($rule === '') {
                    continue;
                }

                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                $failed = match ($name) {
                    'required' => $value === '',
                    'phone'    => $value !== '' && !preg_match('/^[6-9][0-9]{9}$/', $value),
                    'int'      => $value !== '' && !preg_match('/^-?\d+$/', $value),
                    'numeric'  => $value !== '' && !is_numeric($value),
                    'min'      => $value !== '' && mb_strlen($value) < (int) $arg,
                    'max'      => $value !== '' && mb_strlen($value) > (int) $arg,
                    'gte'      => $value !== '' && is_numeric($value) && (float) $value < (float) $arg,
                    'gt'       => $value !== '' && is_numeric($value) && (float) $value <= (float) $arg,
                    'date'     => $value !== '' && !self::isDate($value),
                    'time'     => $value !== '' && !self::isTime($value),
                    'in'       => $value !== '' && !in_array($value, explode(',', (string) $arg), true),
                    default    => false,
                };

                if ($failed) {
                    $errors[$field] = self::message($name, $label, $arg);

                    break;
                }
            }
        }

        return $errors;
    }

    /** Strip spaces, +91 / 0 prefixes and non-digits. */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function isTime(string $value): bool
    {
        return (bool) preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value);
    }

    private static function label(string $field): string
    {
        $labels = [
            'category_id'  => 'Category',
            'area_id'      => 'Area',
            'pay_amount'   => 'Pay amount',
            'pay_type'     => 'Pay basis',
            'starts_on'    => 'Start date',
            'ends_on'      => 'End date',
            'start_time'   => 'Start time',
            'end_time'     => 'End time',
            'headcount'    => 'People needed',
            'full_name'    => 'Full name',
            'org_name'     => 'Business name',
            'contact_name' => 'Contact name',
            'job_public_id' => 'Job',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private static function message(string $rule, string $label, ?string $arg): string
    {
        return match ($rule) {
            'required' => $label . ' is required.',
            'phone'    => $label . ' must be a valid 10-digit Indian mobile number.',
            'int'      => $label . ' must be a whole number.',
            'numeric'  => $label . ' must be a number.',
            'min'      => sprintf('%s must be at least %s characters.', $label, $arg),
            'max'      => sprintf('%s must be at most %s characters.', $label, $arg),
            'gte'      => sprintf('%s must be %s or more.', $label, $arg),
            'gt'       => sprintf('%s must be more than %s.', $label, $arg),
            'date'     => $label . ' must be a valid date.',
            'time'     => $label . ' must be a valid time (HH:MM).',
            'in'       => $label . ' has an invalid value.',
            default    => $label . ' is invalid.',
        };
    }
}
