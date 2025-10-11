<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010160240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, bio LONGTEXT NOT NULL, profile_image VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collectible ADD user_id INT DEFAULT NULL, ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE collectible ADD CONSTRAINT FK_1D1F976EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collectible ADD CONSTRAINT FK_1D1F976E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_1D1F976EA76ED395 ON collectible (user_id)');
        $this->addSql('CREATE INDEX IDX_1D1F976E12469DE2 ON collectible (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collectible DROP FOREIGN KEY FK_1D1F976EA76ED395');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE collectible DROP FOREIGN KEY FK_1D1F976E12469DE2');
        $this->addSql('DROP INDEX IDX_1D1F976EA76ED395 ON collectible');
        $this->addSql('DROP INDEX IDX_1D1F976E12469DE2 ON collectible');
        $this->addSql('ALTER TABLE collectible DROP user_id, DROP category_id');
    }
}
