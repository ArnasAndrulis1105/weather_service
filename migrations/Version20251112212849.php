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
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ALTER COLUMN weather_tags TYPE JSONB USING weather_tags::jsonb');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_products_weather_tags ON products USING GIN (weather_tags jsonb_path_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_products_weather_tags');
        // revert at your own risk:
        // $this->addSql('ALTER TABLE products ALTER COLUMN weather_tags TYPE JSON USING weather_tags::json');
    }
}
