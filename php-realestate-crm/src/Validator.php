<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

final class Validator
{
    public const STAGES = ['new', 'contacted', 'visit_scheduled', 'won', 'lost'];

    /**
     * @param array<string, mixed> $in
     * @return array<string, string> field => message (empty when valid)
     */
    public static function lead(array $in): array
    {
        $errors = [];
        $name = $in['name'] ?? null;
        if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'required, 1-120 characters';
        }
        $email = $in['email'] ?? null;
        if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
            $errors['email'] = 'must be a valid e-mail address';
        }
        $phone = $in['phone'] ?? null;
        if ($phone !== null && (!is_string($phone) || preg_match('/^[0-9+().\- ]{3,30}$/', $phone) !== 1)) {
            $errors['phone'] = 'digits, spaces and + ( ) . - only, 3-30 characters';
        }
        $propertyId = $in['property_id'] ?? null;
        if ($propertyId !== null && (!is_int($propertyId) || $propertyId < 1)) {
            $errors['property_id'] = 'must be a positive integer';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $in
     * @return array<string, string>
     */
    public static function visit(array $in): array
    {
        $errors = [];
        foreach (['lead_id', 'property_id'] as $field) {
            $value = $in[$field] ?? null;
            if (!is_int($value) || $value < 1) {
                $errors[$field] = 'must be a positive integer';
            }
        }
        $when = $in['scheduled_at'] ?? null;
        $parsed = is_string($when) ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $when) : false;
        if ($parsed === false || $parsed->format('Y-m-d H:i:s') !== $when) {
            $errors['scheduled_at'] = 'must be "YYYY-MM-DD HH:MM:SS"';
        }

        return $errors;
    }

    public static function stage(mixed $stage): bool
    {
        return is_string($stage) && in_array($stage, self::STAGES, true);
    }
}
