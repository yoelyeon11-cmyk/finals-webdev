<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth fields and verification flag to user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(255) DEFAULT NULL, ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A76ED395 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649A76ED395 ON user');
        $this->addSql('ALTER TABLE user DROP google_id, DROP is_verified');
    }
}
