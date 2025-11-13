<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112212849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products table (PostgreSQL) with JSONB tags and price NUMERIC(10,2), plus GIN index.';
    }

    public function up(Schema $schema): void
    {
        // Create table (PostgreSQL)
        $this->addSql(<<<'SQL'
CREATE TABLE products (
  id SERIAL NOT NULL PRIMARY KEY,
  sku VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  price NUMERIC(10,2) NOT NULL,
  weather_tags JSONB NOT NULL DEFAULT '[]'::jsonb
)
SQL);

        // JSONB index for fast containment queries
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_products_weather_tags ON products USING GIN (weather_tags jsonb_path_ops)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_products_weather_tags');
        $this->addSql('DROP TABLE IF EXISTS products');
    }
}
