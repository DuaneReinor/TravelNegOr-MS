<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211211721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_hotel DROP FOREIGN KEY FK_E191A5813243BB18');
        $this->addSql('ALTER TABLE user_hotel DROP FOREIGN KEY FK_E191A581A76ED395');
        $this->addSql('ALTER TABLE user_destination DROP FOREIGN KEY FK_97DDF73F816C6140');
        $this->addSql('ALTER TABLE user_destination DROP FOREIGN KEY FK_97DDF73FA76ED395');
        $this->addSql('DROP TABLE user_hotel');
        $this->addSql('DROP TABLE user_destination');
        $this->addSql('ALTER TABLE activity_logs DROP FOREIGN KEY FK_5A2A617E76ED395');
        $this->addSql('DROP INDEX idx_activity_logs_created_at ON activity_logs');
        $this->addSql('DROP INDEX idx_activity_logs_entity ON activity_logs');
        $this->addSql('DROP INDEX idx_activity_logs_action ON activity_logs');
        $this->addSql('ALTER TABLE activity_logs CHANGE old_data old_data JSON DEFAULT NULL, CHANGE new_data new_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_logs ADD CONSTRAINT FK_F34B1DCEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE activity_logs RENAME INDEX idx_activity_logs_user TO IDX_F34B1DCEA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_hotel (user_id INT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_E191A5813243BB18 (hotel_id), INDEX IDX_E191A581A76ED395 (user_id), PRIMARY KEY(user_id, hotel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_destination (user_id INT NOT NULL, destination_id INT NOT NULL, INDEX IDX_97DDF73F816C6140 (destination_id), INDEX IDX_97DDF73FA76ED395 (user_id), PRIMARY KEY(user_id, destination_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_hotel ADD CONSTRAINT FK_E191A5813243BB18 FOREIGN KEY (hotel_id) REFERENCES hotel (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_hotel ADD CONSTRAINT FK_E191A581A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_destination ADD CONSTRAINT FK_97DDF73F816C6140 FOREIGN KEY (destination_id) REFERENCES destination (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_destination ADD CONSTRAINT FK_97DDF73FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_logs DROP FOREIGN KEY FK_F34B1DCEA76ED395');
        $this->addSql('ALTER TABLE activity_logs CHANGE old_data old_data JSON DEFAULT NULL, CHANGE new_data new_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_logs ADD CONSTRAINT FK_5A2A617E76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_activity_logs_created_at ON activity_logs (created_at)');
        $this->addSql('CREATE INDEX idx_activity_logs_entity ON activity_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_activity_logs_action ON activity_logs (action)');
        $this->addSql('ALTER TABLE activity_logs RENAME INDEX idx_f34b1dcea76ed395 TO idx_activity_logs_user');
    }
}
