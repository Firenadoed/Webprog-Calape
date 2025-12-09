<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208005420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to user table';
    }

    public function up(Schema $schema): void
    {
        // Check if column already exists (safe migration)
        $table = $schema->getTable('user');
        if (!$table->hasColumn('created_at')) {
            // 1. Add with NULL allowed
            $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
            
            // 2. Update existing records
            $this->addSql("UPDATE user SET created_at = CURRENT_TIMESTAMP() WHERE created_at IS NULL");
            
            // 3. Change to NOT NULL
            $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP created_at');
    }
}