<?php

namespace AlphaSoft\Sql;

final class ConstructPdoDsn
{
    /**
     * @var string
     */
    private $service;

    public function __construct(string $service)
    {
        $this->service = $service;
    }

    public function __invoke(array $params): string
    {
        $dsn = "{$this->service}:";
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }
}
