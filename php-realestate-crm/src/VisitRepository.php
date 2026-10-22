<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

use PDO;
use PDOException;

final class VisitRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Books a visit and, in the same transaction, moves a new/contacted lead to "visit_scheduled".
     *
     * @return int|null new visit id, or null when the property is already booked at that time
     */
    public function schedule(int $leadId, int $propertyId, string $scheduledAt): ?int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO visits (lead_id, property_id, scheduled_at) VALUES (:lead, :property, :at)'
            );
            $stmt->execute(['lead' => $leadId, 'property' => $propertyId, 'at' => $scheduledAt]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare(
                "UPDATE leads SET stage = 'visit_scheduled' WHERE id = :id AND stage IN ('new', 'contacted')"
            )->execute(['id' => $leadId]);
            $this->pdo->commit();

            return $id;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_visit_slot')) {
                return null;
            }
            throw $e;
        }
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        $stmt = $this->pdo->query(
            'SELECT v.id, v.scheduled_at, v.lead_id, l.name AS lead_name, v.property_id, p.title AS property_title'
            . ' FROM visits v JOIN leads l ON l.id = v.lead_id JOIN properties p ON p.id = v.property_id'
            . ' ORDER BY v.scheduled_at'
        );
        if ($stmt === false) {
            return [];
        }

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }
}
