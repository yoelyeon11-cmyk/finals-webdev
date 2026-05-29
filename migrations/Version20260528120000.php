<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for database-backed admin login sessions on Railway.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('sessions')) {
            return;
        }

        $table = $schema->createTable('sessions');
        $table->addColumn('sess_id', 'string', ['length' => 128]);
        $table->addColumn('sess_data', 'blob');
        $table->addColumn('sess_lifetime', 'integer');
        $table->addColumn('sess_time', 'integer');
        $table->setPrimaryKey(['sess_id']);
        $table->addIndex(['sess_lifetime'], 'sessions_lifetime_idx');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('sessions')) {
            $schema->dropTable('sessions');
        }
    }
}
