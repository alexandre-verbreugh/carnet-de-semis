<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815132526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE observation (id INT AUTO_INCREMENT NOT NULL, observed_at DATE NOT NULL, type VARCHAR(255) NOT NULL, note LONGTEXT DEFAULT NULL, height_cm INT DEFAULT NULL, leaf_count INT DEFAULT NULL, germinated_count INT DEFAULT NULL, harvest_grams INT DEFAULT NULL, created_at DATETIME NOT NULL, sowing_id INT DEFAULT NULL, planter_id INT NOT NULL, INDEX IDX_C576DBE0392BF32A (sowing_id), INDEX IDX_C576DBE0F10BCB1C (planter_id), INDEX idx_observation_observed_at (observed_at), INDEX idx_observation_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, size_bytes INT DEFAULT NULL, uploaded_at DATETIME NOT NULL, observation_id INT DEFAULT NULL, INDEX IDX_14B784181409DD88 (observation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE planter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, location VARCHAR(150) DEFAULT NULL, length_cm INT DEFAULT NULL, width_cm INT DEFAULT NULL, depth_cm INT DEFAULT NULL, substrate_components JSON NOT NULL, top_layer VARCHAR(255) DEFAULT NULL, substrate_note LONGTEXT DEFAULT NULL, exposure VARCHAR(255) DEFAULT NULL, has_drainage TINYINT NOT NULL, filled_at DATE DEFAULT NULL, is_archived TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seed_lot (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(100) DEFAULT NULL, lot_ref VARCHAR(60) DEFAULT NULL, purchased_at DATE DEFAULT NULL, expires_at DATE DEFAULT NULL, initial_seed_count INT DEFAULT NULL, remaining_seed_count INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, species_id INT NOT NULL, INDEX IDX_CD568AC1B2A1D860 (species_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sowing (id INT AUTO_INCREMENT NOT NULL, sown_at DATE NOT NULL, seed_count INT DEFAULT NULL, method VARCHAR(255) NOT NULL, depth_mm INT DEFAULT NULL, status VARCHAR(255) NOT NULL, germinated_at DATE DEFAULT NULL, germinated_count INT DEFAULT NULL, ended_at DATE DEFAULT NULL, failure_reason LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, planter_id INT NOT NULL, species_id INT NOT NULL, seed_lot_id INT DEFAULT NULL, INDEX IDX_BE50E408F10BCB1C (planter_id), INDEX IDX_BE50E408B2A1D860 (species_id), INDEX IDX_BE50E408B548BD8A (seed_lot_id), INDEX idx_sowing_status (status), INDEX idx_sowing_sown_at (sown_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE species (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, variety VARCHAR(100) DEFAULT NULL, family VARCHAR(100) DEFAULT NULL, category VARCHAR(255) NOT NULL, sowing_depth_mm INT DEFAULT NULL, spacing_cm INT DEFAULT NULL, sowing_months JSON NOT NULL, germination_days_min INT DEFAULT NULL, germination_days_max INT DEFAULT NULL, harvest_days_min INT DEFAULT NULL, harvest_days_max INT DEFAULT NULL, germination_temp_min_c INT DEFAULT NULL, exposure VARCHAR(255) DEFAULT NULL, water_need VARCHAR(255) DEFAULT NULL, direct_sow TINYINT NOT NULL, notes LONGTEXT DEFAULT NULL, is_custom TINYINT NOT NULL, UNIQUE INDEX uniq_species_name_variety (name, variety), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weather_day (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, temp_min_c DOUBLE PRECISION DEFAULT NULL, temp_max_c DOUBLE PRECISION DEFAULT NULL, precipitation_mm DOUBLE PRECISION DEFAULT NULL, sunshine_hours DOUBLE PRECISION DEFAULT NULL, synced_at DATETIME NOT NULL, UNIQUE INDEX uniq_weather_day_date (date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0392BF32A FOREIGN KEY (sowing_id) REFERENCES sowing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0F10BCB1C FOREIGN KEY (planter_id) REFERENCES planter (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784181409DD88 FOREIGN KEY (observation_id) REFERENCES observation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seed_lot ADD CONSTRAINT FK_CD568AC1B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id)');
        $this->addSql('ALTER TABLE sowing ADD CONSTRAINT FK_BE50E408F10BCB1C FOREIGN KEY (planter_id) REFERENCES planter (id)');
        $this->addSql('ALTER TABLE sowing ADD CONSTRAINT FK_BE50E408B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id)');
        $this->addSql('ALTER TABLE sowing ADD CONSTRAINT FK_BE50E408B548BD8A FOREIGN KEY (seed_lot_id) REFERENCES seed_lot (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0392BF32A');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0F10BCB1C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784181409DD88');
        $this->addSql('ALTER TABLE seed_lot DROP FOREIGN KEY FK_CD568AC1B2A1D860');
        $this->addSql('ALTER TABLE sowing DROP FOREIGN KEY FK_BE50E408F10BCB1C');
        $this->addSql('ALTER TABLE sowing DROP FOREIGN KEY FK_BE50E408B2A1D860');
        $this->addSql('ALTER TABLE sowing DROP FOREIGN KEY FK_BE50E408B548BD8A');
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
