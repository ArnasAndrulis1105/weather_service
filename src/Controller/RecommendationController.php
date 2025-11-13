<?php
namespace App\Controller;

use App\Service\MeteoLtClient;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Util\CacheKey;


final class RecommendationController extends AbstractController
{
    public function __construct(
        private MeteoLtClient $meteo,
        private RecommendationService $reco,
        private CacheInterface $cache,
    ) {}

    #[Route('/api/products/recommended/{city}', name: 'recommend_city', methods: ['GET'])]
    public function show(string $city): JsonResponse
    {
        $key = CacheKey::make('reco', $city);
        $payload = $this->cache->get($key, function (ItemInterface $item) use ($city) {
            $item->expiresAfter((int)($_ENV['CACHE_TTL_SECONDS'] ?? 300));

            // 1) Get forecast buckets for next 3 days
            $buckets = $this->meteo->nextThreeDayBuckets($city);

            // 2) Map each day to 2 products
            $days = [];
            foreach ($buckets as $b) {
                $days[] = [
                    'weather_forecast' => $b['label'],
                    'date' => $b['date'],
                    'products' => $this->reco->pickForTag($b['tag'], 2),
                ];
            }

            return [
                'city' => $city,
                'recommendations' => $days,
                'source' => [
                    'name' => 'LHMT',
                    'url' => 'https://api.meteo.lt/',
                ],
            ];
        });

        return $this->json($payload);
    }

    private function cacheKey(string $city): string
    {
        // PSR-6 cache keys MUST NOT contain {}()/\@:
        // Use a stable, safe form:
        return 'reco_' . md5(mb_strtolower($city));
        // Or a readable version:
        // $safe = preg_replace('/[{}()\/\\\\@:]/', '-', mb_strtolower($city));
        // return 'reco_' . $safe;
    }
}
