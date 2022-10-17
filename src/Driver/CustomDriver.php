<?php

namespace AlphaSoft\Sql\Driver;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;

final class CustomDriver extends Driver\AbstractMySQLDriver
{
    /**
     * {@inheritdoc}
     *
     * @return Connection
     */
    public function connect(array $params): Connection
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (! empty($params['persistent'])) {
            $driverOptions[\PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $pdo = new \PDO(
                $params['constructPdoDsn']($params),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions
            );
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }

        return new Connection($pdo);
    }
}
