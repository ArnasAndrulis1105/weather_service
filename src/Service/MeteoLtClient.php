<?php

namespace App\Service;

use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MeteoLtClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $baseUrl,
        private readonly int $timeout
    ) {}


    public function resolvePlaceCode(string $city): string
    {
        $places = $this->cache->get('lhmt:places', function (CacheItemInterface $item) {
            $item->expiresAfter(3600);
            $resp = $this->httpClient->request('GET', rtrim($this->baseUrl, '/').'/places', [
                'headers' => [
                    'User-Agent' => 'WeatherReco/1.0 (+https://example.local)',
                    'Accept' => 'application/json',
                ],
                'timeout' => $this->timeout,
            ]);
            return $resp->toArray();
        });


        $needle = mb_strtolower($city);
        foreach ($places as $p) {
            if (($p['code'] ?? '') === $needle) return $p['code'];
            if (isset($p['name']) && mb_strtolower($p['name']) === $needle) return $p['code'];
        }
        foreach ($places as $p) {
            if (isset($p['name']) && str_starts_with(mb_strtolower($p['name']), $needle)) return $p['code'];
        }
        throw new \RuntimeException("Vietovė nerasta LHMT sąraše: {$city}");
    }
    public function nextThreeDayBuckets(string $city): array
    {
        $place = $this->resolvePlaceCode($city);
        $resp = $this->httpClient->request('GET', rtrim($this->baseUrl, '/')."/places/{$place}/forecasts/long-term", [
            'headers' => [
                'User-Agent' => 'WeatherReco/1.0 (+https://example.local)',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);
        if ($resp->getStatusCode() >= 400) {
            throw new \RuntimeException("LHMT long-term prognozės klaida vietovei '{$place}'.");
        }


        $data = $resp->toArray();
        $ts = $data['forecastTimestamps'] ?? [];
        if (!$ts) return [];


        $byDate = [];
        $tz = new \DateTimeZone('Europe/Vilnius');
        foreach ($ts as $item) {
            $dt = new \DateTimeImmutable($item['forecastTimeUtc'] ?? 'now', new \DateTimeZone('UTC'));
            $dLocal = $dt->setTimezone($tz)->format('Y-m-d');
            $byDate[$dLocal][] = $item;
        }


        $out = [];
        $today = (new \DateTimeImmutable('now', $tz))->format('Y-m-d');
        for ($i=0; $i<3; $i++) {
            $date = (new \DateTimeImmutable($today, $tz))->modify("+{$i} day")->format('Y-m-d');
            $slice = $byDate[$date] ?? [];
            if (!$slice) continue;
            $tag = $this->deriveTag($slice);
            $out[] = [
                'date' => $date,
                'tag' => $tag,
                'label'=> $tag,
                'source' => $slice,
            ];
        }
        return $out;
    }


    private function deriveTag(array $hourlies): string
    {
        $hasRain = $hasSnow = false; $maxT = -INF; $minT = INF; $maxWind = 0.0;
        foreach ($hourlies as $h) {
            $cond = strtolower((string)($h['conditionCode'] ?? ''));
            $prec = (float)($h['totalPrecipitation'] ?? 0);
            $t = (float)($h['airTemperature'] ?? 0);
            $w = (float)($h['windSpeed'] ?? 0);
            $maxT = max($maxT, $t); $minT = min($minT, $t); $maxWind = max($maxWind, $w);
            if ($prec > 0 || str_contains($cond, 'rain')) $hasRain = true;
            if (str_contains($cond, 'snow') || str_contains($cond, 'sleet')) $hasSnow = true;
        }
        return $hasSnow ? 'snow' : ($hasRain ? 'rain' : ($maxWind >= 12 ? 'windy' : ($maxT >= 24 ? 'hot' : ($minT <= 2 ? 'cold' : 'sunny'))));
    }
}
