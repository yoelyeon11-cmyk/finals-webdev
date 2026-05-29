<?php

namespace App\Session;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

final class PdoSessionHandlerFactory
{
    public function create(Connection $connection): PdoSessionHandler
    {
        $pdo = $connection->getNativeConnection();
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('Session storage requires a PDO database connection.');
        }

        return new PdoSessionHandler($pdo, [
            'db_table' => 'sessions',
            'db_id_col' => 'sess_id',
            'db_data_col' => 'sess_data',
            'db_lifetime_col' => 'sess_lifetime',
            'db_time_col' => 'sess_time',
            'lock_mode' => PdoSessionHandler::LOCK_NONE,
        ]);
    }
}
