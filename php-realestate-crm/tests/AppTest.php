<?php

declare(strict_types=1);

namespace DcTech\RealEstate\Tests;

use DcTech\RealEstate\App;
use DcTech\RealEstate\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/** Integration tests against a real MySQL (CI service container or `docker compose up db`). */
final class AppTest extends TestCase
{
    private PDO $pdo;
    private App $app;

    protected function setUp(): void
    {
        if (getenv('DB_HOST') === false) {
            self::markTestSkipped('Set DB_HOST/DB_NAME/DB_USER/DB_PASSWORD to run integration tests.');
        }
        $this->pdo = Database::fromEnv();
        Database::reset($this->pdo, dirname(__DIR__) . '/db');
        $this->app = new App($this->pdo);
    }

    public function testHealth(): void
    {
        self::assertSame([200, ['status' => 'ok']], $this->app->handle('GET', '/health'));
    }

    public function testPropertyFilters(): void
    {
        [$status, $body] = $this->app->handle('GET', '/properties', ['city' => 'Lakeside', 'status' => 'available']);
        self::assertSame(200, $status);
        /** @var list<array<string, mixed>> $rows */
        $rows = $body['data'];
        self::assertCount(1, $rows);
        self::assertSame('Three-bedroom townhouse', $rows[0]['title']);

        [, $cheap] = $this->app->handle('GET', '/properties', ['max_price' => '200000']);
        self::assertCount(1, $cheap['data']);
    }

    public function testUnknownPropertyIs404(): void
    {
        self::assertSame(404, $this->app->handle('GET', '/properties/9999')[0]);
    }

    public function testCreateLeadValidatesAndPersists(): void
    {
        [$status, $body] = $this->app->handle('POST', '/leads', [], ['name' => '', 'email' => 'x']);
        self::assertSame(422, $status);
        self::assertArrayHasKey('errors', $body);

        [$status, $lead] = $this->app->handle('POST', '/leads', [], [
            'name' => 'Casey Test', 'email' => 'casey@example.com', 'property_id' => 1,
        ]);
        self::assertSame(201, $status);
        self::assertSame('new', $lead['stage']);

        [$status] = $this->app->handle('POST', '/leads', [], [
            'name' => 'Casey Test', 'email' => 'casey@example.com', 'property_id' => 999,
        ]);
        self::assertSame(422, $status);
    }

    public function testStageUpdate(): void
    {
        self::assertSame(422, $this->app->handle('PATCH', '/leads/1', [], ['stage' => 'archived'])[0]);
        self::assertSame(404, $this->app->handle('PATCH', '/leads/999', [], ['stage' => 'won'])[0]);
        [$status, $lead] = $this->app->handle('PATCH', '/leads/1', [], ['stage' => 'contacted']);
        self::assertSame(200, $status);
        self::assertSame('contacted', $lead['stage']);
    }

    public function testSchedulingVisitMovesLeadAndBlocksDoubleBooking(): void
    {
        $visit = ['lead_id' => 1, 'property_id' => 1, 'scheduled_at' => '2026-11-02 10:00:00'];
        self::assertSame(201, $this->app->handle('POST', '/visits', [], $visit)[0]);
        self::assertSame('visit_scheduled', $this->app->handle('GET', '/leads', ['stage' => 'visit_scheduled'])[1]['data'][0]['stage']);

        [$status] = $this->app->handle('POST', '/visits', [], [...$visit, 'lead_id' => 2]);
        self::assertSame(409, $status);

        self::assertCount(1, $this->app->handle('GET', '/visits')[1]['data']);
    }

    public function testVisitRulesForUnavailableAndMissingRecords(): void
    {
        $base = ['lead_id' => 1, 'property_id' => 4, 'scheduled_at' => '2026-11-03 09:00:00'];
        self::assertSame(409, $this->app->handle('POST', '/visits', [], $base)[0]);
        self::assertSame(404, $this->app->handle('POST', '/visits', [], [...$base, 'property_id' => 999])[0]);
        self::assertSame(404, $this->app->handle('POST', '/visits', [], [...$base, 'lead_id' => 999])[0]);
        self::assertSame(422, $this->app->handle('POST', '/visits', [], ['lead_id' => 1])[0]);
    }

    public function testUnknownRoute(): void
    {
        self::assertSame(404, $this->app->handle('DELETE', '/leads/1')[0]);
    }
}
