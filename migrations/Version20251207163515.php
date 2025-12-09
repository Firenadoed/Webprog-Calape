<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207163515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collectible DROP FOREIGN KEY FK_1D1F976EA76ED395');
        $this->addSql('ALTER TABLE collectible ADD CONSTRAINT FK_1D1F976EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE listing DROP FOREIGN KEY FK_CB0048D49700322F');
        $this->addSql('ALTER TABLE listing DROP FOREIGN KEY FK_CB0048D4A76ED395');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D49700322F FOREIGN KEY (collectible_id) REFERENCES collectible (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collectible DROP FOREIGN KEY FK_1D1F976EA76ED395');
        $this->addSql('ALTER TABLE collectible ADD CONSTRAINT FK_1D1F976EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE listing DROP FOREIGN KEY FK_CB0048D4A76ED395');
        $this->addSql('ALTER TABLE listing DROP FOREIGN KEY FK_CB0048D49700322F');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D49700322F FOREIGN KEY (collectible_id) REFERENCES collectible (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
