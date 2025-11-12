<?php


namespace App\Service;


use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;


final class RecommendationService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly EntityManagerInterface $em
    ) {}


    public function recommendForBuckets(array $buckets): array
    {
        $out = [];
        foreach ($buckets as $b) {
            $tag = $b['tag'];
            $rows = $this->products->findForWeather($tag, 10);
            usort($rows, fn($a,$b) => $a['price'] <=> $b['price']);


            if (count($rows) < 2) {
                $conn = $this->em->getConnection();
                $fallback = $conn->executeQuery('SELECT sku,name,price FROM products ORDER BY price ASC LIMIT 2')->fetchAllAssociative();
                $bySku = [];
                foreach (array_merge($rows, $fallback) as $r) if (!isset($bySku[$r['sku']])) $bySku[$r['sku']] = $r;
                $rows = array_slice(array_values($bySku), 0, 2);
            } else {
                $rows = array_slice($rows, 0, 2);
            }


            $out[] = [
                'weather_forecast' => $b['label'],
                'date' => $b['date'],
                'products' => array_map(fn($r) => [
                    'sku' => $r['sku'], 'name' => $r['name'], 'price' => (float)$r['price']
                ], $rows),
            ];
        }
        return $out;
    }
}
