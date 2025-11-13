<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    { parent::__construct($registry, Product::class); }

    /** @return array<int,array<string,mixed>> */
    public function findForWeather(string $tag, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // JSONB containment: does weather_tags contain "rain"?
        // Ensure column type is JSONB (or cast weather_tags::jsonb).
        $sql = <<<'SQL'
SELECT sku, name, price, weather_tags
FROM products
WHERE weather_tags::jsonb @> :needle::jsonb
ORDER BY price ASC
LIMIT :lim
SQL;

        $needle = json_encode([$tag]); // ["rain"]
        return $conn->prepare($sql)
            ->executeQuery(['needle' => $needle, 'lim' => $limit])
            ->fetchAllAssociative();
    }

}
