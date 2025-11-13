Weather Recommendations API (Symfony + PostgreSQL + Docker)

A small Symfony service that recommends products for the next 3 days based on the LHMT (Meteo.lt) weather forecast for a given Lithuanian city.

Features

  REST endpoint: GET /api/products/recommended/{city} (e.g., /api/products/recommended/Vilnius)
  
  Pulls forecasts from Meteo.lt (https://api.meteo.lt/v1) and returns 2 products per day
  
  Product data stored in PostgreSQL; seeded via Doctrine Fixtures
  
  5‑minute cache of API responses (PSR‑6) with safe cache keys
  
  Dockerized stack: Nginx + PHP‑FPM + Symfony + Postgres

Launching with Docker:
git clone <YOUR_REPO_URL> weather_service
cd weather_service

docker compose -f docker-compose.yml up -d --build

docker compose -f docker-compose.yml exec app php bin/console doctrine:migrations:migrate -n

docker compose -f docker-compose.yml exec app php bin/console doctrine:fixtures:load -n

Hitting the endpoint:

GET http://127.0.0.1:8000/api/products/recommended/{city}
