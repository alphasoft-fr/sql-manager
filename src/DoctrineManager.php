<?php

namespace AlphaSoft\Sql;

use AlphaSoft\Sql\Driver\CustomDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DoctrineManager
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        string    $host,
        string    $dbs,
        string    $user,
        ?string   $password = null,
        ?callable $constructPdoDsn = null
    )
    {
        $this->connection = DriverManager::getConnection([
            'dbname' => $dbs,
            'user' => $user,
            'password' => $password,
            'host' => $host,
            'driverClass' => CustomDriver::class,
            'constructPdoDsn' => $constructPdoDsn ?: new ConstructPdoDsn('mysql'),
        ]);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
