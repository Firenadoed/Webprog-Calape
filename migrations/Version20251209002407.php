<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209002407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraint for created_by_id';
    }

    public function up(Schema $schema): void
    {
        // This is the SAFE version that will work
        
        // 1. First, check if we need to create a default user
        $result = $this->connection->executeQuery('SELECT COUNT(*) as count FROM user');
        $userCount = $result->fetchAssociative()['count'];
        
        if ($userCount == 0) {
            // Create a default admin user
            $this->addSql("
                INSERT INTO user (username, roles, password, profile_image, bio, status, created_at) 
                VALUES (
                    'system_admin', 
                    '[\"ROLE_ADMIN\"]', 
                    '\$2y\$13\$DfL5G8pN9qR2T3V.PasswordHashPlaceholder', 
                    'default.jfif', 
                    'System administrator', 
                    1, 
                    NOW()
                )
            ");
        }
        
        // 2. Get a default user ID
        $result = $this->connection->executeQuery('
            SELECT id FROM user 
            WHERE roles LIKE "%ROLE_ADMIN%" 
            ORDER BY id 
            LIMIT 1
        ');
        $defaultUser = $result->fetchAssociative();
        
        if (!$defaultUser) {
            $result = $this->connection->executeQuery('SELECT id FROM user ORDER BY id LIMIT 1');
            $defaultUser = $result->fetchAssociative();
        }
        
        $defaultUserId = $defaultUser['id'] ?? 1;
        
        // 3. Update NULL values
        $this->addSql("
            UPDATE collectible 
            SET created_by_id = :userId 
            WHERE created_by_id IS NULL
        ", ['userId' => $defaultUserId]);
        
        // 4. Make column NOT NULL
        $this->addSql('ALTER TABLE collectible MODIFY created_by_id INT NOT NULL');
        
        // 5. Add foreign key
        $this->addSql('ALTER TABLE collectible ADD CONSTRAINT FK_1D1F976EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        
        // 6. Create index
        $this->addSql('CREATE INDEX IDX_1D1F976EB03A8386 ON collectible (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collectible DROP FOREIGN KEY FK_1D1F976EB03A8386');
        $this->addSql('DROP INDEX IDX_1D1F976EB03A8386 ON collectible');
        $this->addSql('ALTER TABLE collectible MODIFY created_by_id INT DEFAULT NULL');
    }
}