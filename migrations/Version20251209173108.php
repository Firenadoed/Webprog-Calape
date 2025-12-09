<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209173108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE listing ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D4B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_CB0048D4B03A8386 ON listing (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE listing DROP FOREIGN KEY FK_CB0048D4B03A8386');
        $this->addSql('DROP INDEX IDX_CB0048D4B03A8386 ON listing');
        $this->addSql('ALTER TABLE listing DROP created_by_id');
    }
}
