<?php


namespace App\Controller;


use App\Service\MeteoLtClient;
use App\Service\RecommendationService;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;


final class RecommendationController extends AbstractController
{
    public function __construct(
        private readonly MeteoLtClient $lhmt,
        private readonly RecommendationService $reco,
        private readonly CacheInterface $cache
    ) {}


    #[Route('/api/products/recommended/{city}', name: 'recommend_city', methods: ['GET'])]
    public function show(string $city): Response
    {
        $payload = $this->cache->get("reco:$city", function (CacheItemInterface $item) use ($city) {
            $item->expiresAfter((int)($_ENV['CACHE_TTL_SECONDS'] ?? 300));
            $buckets = $this->lhmt->nextThreeDayBuckets($city);
            $recs = $this->reco->recommendForBuckets($buckets);
            return [
                'city' => $city,
                'recommendations' => $recs,
                'source' => [
                    'name' => 'LHMT',
                    'url' => 'https://api.meteo.lt/',
                    'license' => 'CC BY-SA 4.0',
                    'attribution' => 'Weather data provided by LHMT (api.meteo.lt), licensed under CC BY-SA 4.0',
                    'disclaimer' => 'Duomenys teikiami tokie, kokie yra, be jokių garantijų.',
                ],
            ];
        });


        return new JsonResponse($payload, 200, [
            'X-Data-Source' => 'LHMT (api.meteo.lt)',
            'X-Attribution' => 'LHMT (api.meteo.lt), CC BY-SA 4.0',
        ]);
    }
}
