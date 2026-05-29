<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate sessions table with MySQL-compatible schema for Symfony PdoSessionHandler.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sessions');
        $this->addSql(<<<'SQL'
            CREATE TABLE sessions (
                sess_id VARBINARY(128) NOT NULL PRIMARY KEY,
                sess_data BLOB NOT NULL,
                sess_lifetime INT UNSIGNED NOT NULL,
                sess_time INT UNSIGNED NOT NULL,
                INDEX sessions_lifetime_idx (sess_lifetime)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sessions');
    }
}
