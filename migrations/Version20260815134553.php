<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815134553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE observation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, observed_at DATE NOT NULL, type VARCHAR(255) NOT NULL, note CLOB DEFAULT NULL, height_cm INTEGER DEFAULT NULL, leaf_count INTEGER DEFAULT NULL, germinated_count INTEGER DEFAULT NULL, harvest_grams INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, sowing_id INTEGER DEFAULT NULL, planter_id INTEGER NOT NULL, CONSTRAINT FK_C576DBE0392BF32A FOREIGN KEY (sowing_id) REFERENCES sowing (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C576DBE0F10BCB1C FOREIGN KEY (planter_id) REFERENCES planter (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C576DBE0392BF32A ON observation (sowing_id)');
        $this->addSql('CREATE INDEX IDX_C576DBE0F10BCB1C ON observation (planter_id)');
        $this->addSql('CREATE INDEX idx_observation_observed_at ON observation (observed_at)');
        $this->addSql('CREATE INDEX idx_observation_type ON observation (type)');
        $this->addSql('CREATE TABLE photo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, path VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, size_bytes INTEGER DEFAULT NULL, uploaded_at DATETIME NOT NULL, observation_id INTEGER DEFAULT NULL, CONSTRAINT FK_14B784181409DD88 FOREIGN KEY (observation_id) REFERENCES observation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_14B784181409DD88 ON photo (observation_id)');
        $this->addSql('CREATE TABLE planter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, location VARCHAR(150) DEFAULT NULL, length_cm INTEGER DEFAULT NULL, width_cm INTEGER DEFAULT NULL, depth_cm INTEGER DEFAULT NULL, substrate_components CLOB NOT NULL, top_layer VARCHAR(255) DEFAULT NULL, substrate_note CLOB DEFAULT NULL, exposure VARCHAR(255) DEFAULT NULL, has_drainage BOOLEAN NOT NULL, filled_at DATE DEFAULT NULL, is_archived BOOLEAN NOT NULL)');
        $this->addSql('CREATE TABLE seed_lot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, brand VARCHAR(100) DEFAULT NULL, lot_ref VARCHAR(60) DEFAULT NULL, purchased_at DATE DEFAULT NULL, expires_at DATE DEFAULT NULL, initial_seed_count INTEGER DEFAULT NULL, remaining_seed_count INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, species_id INTEGER NOT NULL, CONSTRAINT FK_CD568AC1B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CD568AC1B2A1D860 ON seed_lot (species_id)');
        $this->addSql('CREATE TABLE sowing (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sown_at DATE NOT NULL, seed_count INTEGER DEFAULT NULL, method VARCHAR(255) NOT NULL, depth_mm INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, germinated_at DATE DEFAULT NULL, germinated_count INTEGER DEFAULT NULL, ended_at DATE DEFAULT NULL, failure_reason CLOB DEFAULT NULL, notes CLOB DEFAULT NULL, planter_id INTEGER NOT NULL, species_id INTEGER NOT NULL, seed_lot_id INTEGER DEFAULT NULL, CONSTRAINT FK_BE50E408F10BCB1C FOREIGN KEY (planter_id) REFERENCES planter (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BE50E408B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BE50E408B548BD8A FOREIGN KEY (seed_lot_id) REFERENCES seed_lot (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BE50E408F10BCB1C ON sowing (planter_id)');
        $this->addSql('CREATE INDEX IDX_BE50E408B2A1D860 ON sowing (species_id)');
        $this->addSql('CREATE INDEX IDX_BE50E408B548BD8A ON sowing (seed_lot_id)');
        $this->addSql('CREATE INDEX idx_sowing_status ON sowing (status)');
        $this->addSql('CREATE INDEX idx_sowing_sown_at ON sowing (sown_at)');
        $this->addSql('CREATE TABLE species (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, variety VARCHAR(100) DEFAULT NULL, family VARCHAR(100) DEFAULT NULL, category VARCHAR(255) NOT NULL, sowing_depth_mm INTEGER DEFAULT NULL, spacing_cm INTEGER DEFAULT NULL, sowing_months CLOB NOT NULL, germination_days_min INTEGER DEFAULT NULL, germination_days_max INTEGER DEFAULT NULL, harvest_days_min INTEGER DEFAULT NULL, harvest_days_max INTEGER DEFAULT NULL, germination_temp_min_c INTEGER DEFAULT NULL, exposure VARCHAR(255) DEFAULT NULL, water_need VARCHAR(255) DEFAULT NULL, direct_sow BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, is_custom BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_species_name_variety ON species (name, variety)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');
        $this->addSql('CREATE TABLE weather_day (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, temp_min_c DOUBLE PRECISION DEFAULT NULL, temp_max_c DOUBLE PRECISION DEFAULT NULL, precipitation_mm DOUBLE PRECISION DEFAULT NULL, sunshine_hours DOUBLE PRECISION DEFAULT NULL, synced_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_weather_day_date ON weather_day (date)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE observation');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE planter');
        $this->addSql('DROP TABLE seed_lot');
        $this->addSql('DROP TABLE sowing');
        $this->addSql('DROP TABLE species');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE weather_day');
    }
}
