<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

use PDO;

/** Framework-free JSON API: routing + rules, no I/O, so it is testable without an HTTP server. */
final class App
{
    private readonly PropertyRepository $properties;
    private readonly LeadRepository $leads;
    private readonly VisitRepository $visits;

    public function __construct(PDO $pdo)
    {
        $this->properties = new PropertyRepository($pdo);
        $this->leads = new LeadRepository($pdo);
        $this->visits = new VisitRepository($pdo);
    }

    /**
     * @param array<string, string> $query
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<mixed>} status and JSON-serialisable payload
     */
    public function handle(string $method, string $path, array $query = [], array $body = []): array
    {
        $path = '/' . trim($path, '/');

        if ($method === 'GET' && $path === '/health') {
            return [200, ['status' => 'ok']];
        }
        if ($method === 'GET' && $path === '/properties') {
            return [200, ['data' => $this->properties->search($query)]];
        }
        if ($method === 'GET' && preg_match('#^/properties/(\d+)$#', $path, $m) === 1) {
            $property = $this->properties->find((int) $m[1]);

            return $property === null ? self::notFound('property') : [200, $property];
        }
        if ($path === '/leads') {
            if ($method === 'GET') {
                $stage = $query['stage'] ?? null;
                if ($stage !== null && !Validator::stage($stage)) {
                    return [422, ['errors' => ['stage' => 'unknown stage']]];
                }

                return [200, ['data' => $this->leads->list($stage)]];
            }
            if ($method === 'POST') {
                return $this->createLead($body);
            }
        }
        if ($method === 'PATCH' && preg_match('#^/leads/(\d+)$#', $path, $m) === 1) {
            return $this->updateStage((int) $m[1], $body);
        }
        if ($path === '/visits') {
            if ($method === 'GET') {
                return [200, ['data' => $this->visits->list()]];
            }
            if ($method === 'POST') {
                return $this->scheduleVisit($body);
            }
        }

        return [404, ['error' => 'route not found']];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<mixed>}
     */
    private function createLead(array $body): array
    {
        $errors = Validator::lead($body);
        if ($errors !== []) {
            return [422, ['errors' => $errors]];
        }
        if (isset($body['property_id']) && $this->properties->find((int) $body['property_id']) === null) {
            return [422, ['errors' => ['property_id' => 'property does not exist']]];
        }
        $lead = $this->leads->find($this->leads->create($body));

        return [201, $lead ?? []];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<mixed>}
     */
    private function updateStage(int $id, array $body): array
    {
        $stage = $body['stage'] ?? null;
        if (!Validator::stage($stage)) {
            return [422, ['errors' => ['stage' => 'one of: ' . implode(', ', Validator::STAGES)]]];
        }
        if ($this->leads->find($id) === null) {
            return self::notFound('lead');
        }
        $this->leads->setStage($id, (string) $stage);

        return [200, $this->leads->find($id) ?? []];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<mixed>}
     */
    private function scheduleVisit(array $body): array
    {
        $errors = Validator::visit($body);
        if ($errors !== []) {
            return [422, ['errors' => $errors]];
        }
        $leadId = (int) $body['lead_id'];
        $propertyId = (int) $body['property_id'];
        if ($this->leads->find($leadId) === null) {
            return self::notFound('lead');
        }
        $property = $this->properties->find($propertyId);
        if ($property === null) {
            return self::notFound('property');
        }
        if ($property['status'] !== 'available') {
            return [409, ['error' => 'property is not available for visits']];
        }
        $id = $this->visits->schedule($leadId, $propertyId, (string) $body['scheduled_at']);

        return $id === null
            ? [409, ['error' => 'property already has a visit at that time']]
            : [201, ['id' => $id, 'lead_id' => $leadId, 'property_id' => $propertyId, 'scheduled_at' => $body['scheduled_at']]];
    }

    /** @return array{0: int, 1: array<mixed>} */
    private static function notFound(string $what): array
    {
        return [404, ['error' => "{$what} not found"]];
    }
}
