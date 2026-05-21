<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token fields to user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD email_verification_token VARCHAR(64) DEFAULT NULL, ADD email_verification_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6495BAEBB1C ON user (email_verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_8D93D6495BAEBB1C ON user');
        $this->addSql('ALTER TABLE user DROP email_verification_token, DROP email_verification_token_expires_at');
    }
}

