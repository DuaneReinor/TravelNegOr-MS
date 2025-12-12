<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create activity_logs table
 */
final class Version20251211013900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_logs table for tracking user activities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT NOT NULL, 
            action VARCHAR(255) NOT NULL, 
            entity_type VARCHAR(255) NOT NULL, 
            entity_id INT DEFAULT NULL, 
            entity_name VARCHAR(255) DEFAULT NULL, 
            user_id INT DEFAULT NULL, 
            user_email VARCHAR(255) DEFAULT NULL, 
            description LONGTEXT DEFAULT NULL, 
            old_data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", 
            new_data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", 
            ip_address VARCHAR(255) DEFAULT NULL, 
            user_agent VARCHAR(255) DEFAULT NULL, 
            created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX idx_activity_logs_action ON activity_logs (action)');
        $this->addSql('CREATE INDEX idx_activity_logs_entity ON activity_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_activity_logs_user ON activity_logs (user_id)');
        $this->addSql('CREATE INDEX idx_activity_logs_created_at ON activity_logs (created_at)');

        $this->addSql('ALTER TABLE activity_logs ADD CONSTRAINT FK_5A2A617E76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_logs');
    }
}