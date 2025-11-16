<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113175436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE driver (id SERIAL NOT NULL, user_id INT NOT NULL, vehicule_model VARCHAR(50) NOT NULL, vehicule_type VARCHAR(50) NOT NULL, vehicule_color VARCHAR(50) NOT NULL, current_latitude DOUBLE PRECISION NOT NULL, current_longitude DOUBLE PRECISION NOT NULL, licence_number VARCHAR(50) NOT NULL, verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_verified BOOLEAN NOT NULL, is_available BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_11667CD9A76ED395 ON driver (user_id)');
        $this->addSql('COMMENT ON COLUMN driver.verified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE rating (id SERIAL NOT NULL, ride_id INT NOT NULL, rater_id INT NOT NULL, rated_id INT NOT NULL, score DOUBLE PRECISION NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8892622302A8A70 ON rating (ride_id)');
        $this->addSql('CREATE INDEX IDX_D88926223FC1CD0A ON rating (rater_id)');
        $this->addSql('CREATE INDEX IDX_D88926224AB3C549 ON rating (rated_id)');
        $this->addSql('CREATE TABLE ride (id SERIAL NOT NULL, driver_id INT NOT NULL, passenger_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL, pick_up_address VARCHAR(255) NOT NULL, pick_up_latitude DOUBLE PRECISION NOT NULL, pick_up_longitude DOUBLE PRECISION NOT NULL, dropoff_address VARCHAR(255) NOT NULL, dropoff_latitude DOUBLE PRECISION NOT NULL, dropoff_longitude DOUBLE PRECISION NOT NULL, estimated_distance DOUBLE PRECISION NOT NULL, estimated_price DOUBLE PRECISION NOT NULL, estimated_duration DOUBLE PRECISION DEFAULT NULL, final_price DOUBLE PRECISION DEFAULT NULL, vehicule_type VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9B3D7CD0C3423909 ON ride (driver_id)');
        $this->addSql('CREATE INDEX IDX_9B3D7CD04502E565 ON ride (passenger_id)');
        $this->addSql('COMMENT ON COLUMN ride.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ride.accepted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ride.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ride.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles TEXT NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, usertype VARCHAR(20) NOT NULL, rating DOUBLE PRECISION DEFAULT NULL, total_rides INT DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN "user".roles IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE driver ADD CONSTRAINT FK_11667CD9A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D88926223FC1CD0A FOREIGN KEY (rater_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D88926224AB3C549 FOREIGN KEY (rated_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0C3423909 FOREIGN KEY (driver_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD04502E565 FOREIGN KEY (passenger_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE driver DROP CONSTRAINT FK_11667CD9A76ED395');
        $this->addSql('ALTER TABLE rating DROP CONSTRAINT FK_D8892622302A8A70');
        $this->addSql('ALTER TABLE rating DROP CONSTRAINT FK_D88926223FC1CD0A');
        $this->addSql('ALTER TABLE rating DROP CONSTRAINT FK_D88926224AB3C549');
        $this->addSql('ALTER TABLE ride DROP CONSTRAINT FK_9B3D7CD0C3423909');
        $this->addSql('ALTER TABLE ride DROP CONSTRAINT FK_9B3D7CD04502E565');
        $this->addSql('DROP TABLE driver');
        $this->addSql('DROP TABLE rating');
        $this->addSql('DROP TABLE ride');
        $this->addSql('DROP TABLE "user"');
    }
}
