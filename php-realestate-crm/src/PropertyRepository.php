<?php

declare(strict_types=1);

namespace DcTech\RealEstate;

use PDO;

final class PropertyRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string, string> $filters city, status, max_price, min_bedrooms
     * @return list<array<string, mixed>>
     */
    public function search(array $filters): array
    {
        $where = [];
        $params = [];
        if (isset($filters['city']) && $filters['city'] !== '') {
            $where[] = 'city = :city';
            $params['city'] = $filters['city'];
        }
        if (isset($filters['status']) && in_array($filters['status'], ['available', 'reserved', 'sold'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['max_price']) && ctype_digit($filters['max_price'])) {
            $where[] = 'price_usd <= :max_price';
            $params['max_price'] = (int) $filters['max_price'];
        }
        if (isset($filters['min_bedrooms']) && ctype_digit($filters['min_bedrooms'])) {
            $where[] = 'bedrooms >= :min_bedrooms';
            $params['min_bedrooms'] = (int) $filters['min_bedrooms'];
        }
        $sql = 'SELECT * FROM properties'
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY price_usd, id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM properties WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}
