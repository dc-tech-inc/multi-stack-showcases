<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

use PDO;

final class LeadRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $in already validated */
    public function create(array $in): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO leads (name, email, phone, property_id, notes)'
            . ' VALUES (:name, :email, :phone, :property_id, :notes)'
        );
        $stmt->execute([
            'name' => trim((string) $in['name']),
            'email' => (string) $in['email'],
            'phone' => isset($in['phone']) ? (string) $in['phone'] : null,
            'property_id' => isset($in['property_id']) ? (int) $in['property_id'] : null,
            'notes' => isset($in['notes']) ? (string) $in['notes'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM leads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function list(?string $stage): array
    {
        if ($stage === null) {
            $stmt = $this->pdo->query('SELECT * FROM leads ORDER BY id');
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM leads WHERE stage = :stage ORDER BY id');
            $stmt->execute(['stage' => $stage]);
        }
        if ($stmt === false) {
            return [];
        }

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function setStage(int $id, string $stage): void
    {
        $stmt = $this->pdo->prepare('UPDATE leads SET stage = :stage WHERE id = :id');
        $stmt->execute(['stage' => $stage, 'id' => $id]);
    }
}
