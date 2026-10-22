<?php

declare(strict_types=1);

namespace DcTech\RealEstate\Tests;

use DcTech\RealEstate\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidLeadHasNoErrors(): void
    {
        self::assertSame([], Validator::lead(['name' => 'Alex Example', 'email' => 'alex@example.com', 'phone' => '555-0100']));
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('invalidLeads')]
    public function testInvalidLeadReportsField(array $input, string $field): void
    {
        self::assertArrayHasKey($field, Validator::lead($input));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidLeads(): iterable
    {
        yield 'missing name' => [['email' => 'a@example.com'], 'name'];
        yield 'blank name' => [['name' => '  ', 'email' => 'a@example.com'], 'name'];
        yield 'bad email' => [['name' => 'A', 'email' => 'not-an-email'], 'email'];
        yield 'bad phone' => [['name' => 'A', 'email' => 'a@example.com', 'phone' => 'call me'], 'phone'];
        yield 'string property id' => [['name' => 'A', 'email' => 'a@example.com', 'property_id' => '3'], 'property_id'];
    }

    public function testVisitRequiresRealCalendarDate(): void
    {
        $ok = ['lead_id' => 1, 'property_id' => 1, 'scheduled_at' => '2026-11-02 10:00:00'];
        self::assertSame([], Validator::visit($ok));
        self::assertArrayHasKey('scheduled_at', Validator::visit([...$ok, 'scheduled_at' => '2026-02-31 10:00:00']));
        self::assertArrayHasKey('lead_id', Validator::visit([...$ok, 'lead_id' => 0]));
    }

    public function testStage(): void
    {
        self::assertTrue(Validator::stage('won'));
        self::assertFalse(Validator::stage('archived'));
        self::assertFalse(Validator::stage(1));
    }
}
