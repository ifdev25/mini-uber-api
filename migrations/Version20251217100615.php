<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217100615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add database indexes for performance optimization';
    }

    public function up(Schema $schema): void
    {
        // Add indexes for frequently filtered columns to improve query performance

        // User table indexes (camelCase columns)
        $this->addSql('CREATE INDEX idx_user_usertype ON "user" (usertype)');
        $this->addSql('CREATE INDEX idx_user_rating ON "user" (rating)');
        $this->addSql('CREATE INDEX idx_user_createdat ON "user" (createdat)');

        // Ride table indexes (snake_case columns)
        $this->addSql('CREATE INDEX idx_ride_status ON ride (status)');
        $this->addSql('CREATE INDEX idx_ride_vehicletype ON ride (vehicle_type)');
        $this->addSql('CREATE INDEX idx_ride_createdat ON ride (created_at)');
        $this->addSql('CREATE INDEX idx_ride_passenger ON ride (passenger_id)');
        $this->addSql('CREATE INDEX idx_ride_driver ON ride (driver_id)');
        $this->addSql('CREATE INDEX idx_ride_completedat ON ride (completed_at)');

        // Driver table indexes (camelCase columns)
        $this->addSql('CREATE INDEX idx_driver_isavailable ON driver (isavailable)');
        $this->addSql('CREATE INDEX idx_driver_isverified ON driver (isverified)');
        $this->addSql('CREATE INDEX idx_driver_vehicletype ON driver (vehicletype)');

        // Rating table indexes
        $this->addSql('CREATE INDEX idx_rating_score ON rating (score)');
        $this->addSql('CREATE INDEX idx_rating_ride ON rating (ride_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop all indexes created in up()

        $this->addSql('DROP INDEX IF EXISTS idx_user_usertype');
        $this->addSql('DROP INDEX IF EXISTS idx_user_rating');
        $this->addSql('DROP INDEX IF EXISTS idx_user_createdat');

        $this->addSql('DROP INDEX IF EXISTS idx_ride_status');
        $this->addSql('DROP INDEX IF EXISTS idx_ride_vehicletype');
        $this->addSql('DROP INDEX IF EXISTS idx_ride_createdat');
        $this->addSql('DROP INDEX IF EXISTS idx_ride_passenger');
        $this->addSql('DROP INDEX IF EXISTS idx_ride_driver');
        $this->addSql('DROP INDEX IF EXISTS idx_ride_completedat');

        $this->addSql('DROP INDEX IF EXISTS idx_driver_isavailable');
        $this->addSql('DROP INDEX IF EXISTS idx_driver_isverified');
        $this->addSql('DROP INDEX IF EXISTS idx_driver_vehicletype');

        $this->addSql('DROP INDEX IF EXISTS idx_rating_score');
        $this->addSql('DROP INDEX IF EXISTS idx_rating_ride');

        $this->addSql('CREATE SCHEMA public');
    }
}
