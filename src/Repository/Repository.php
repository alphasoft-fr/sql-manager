<?php

namespace AlphaSoft\Sql\Repository;

use AlphaSoft\DataModel\AbstractModel;
use AlphaSoft\DataModel\Helper\ModelHelper;
use AlphaSoft\Sql\DoctrineManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ReflectionClass;

abstract class Repository
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(DoctrineManager $manager)
    {
        $this->connection = $manager->getConnection();
    }

    abstract public function getTable(): string;
    abstract public function getModelName(): string;
    /**
     * @return array<string>
     */
    abstract public function getProperties(): array;

    public function findOneBy(array $arguments = [], array $orderBy = []): ?AbstractModel
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, null);
        $item = $query->fetchAssociative();
        if ($item === false) {
            return null;
        }
        return $this->toObject(ModelHelper::getReflection($this->getModelName()), $item);
    }

    public function findBy(array $arguments = [], array $orderBy = [], ?int $limit = null): array
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, $limit);
        $data = $query->fetchAllAssociative();
        $collection = [];
        $reflectionClass = ModelHelper::getReflection($this->getModelName());
        foreach ($data as $item) {
            $model = self::toObject($reflectionClass, $item);
            $collection[] = $model;
        }
        return $collection;
    }

    public function insert(AbstractModel $model): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->insert($this->getTable());
        foreach ($model->toArray() as $property => $value) {
            $query->setValue($property, $query->createPositionalParameter($value));
        }
        $rows = $query->executeStatement();
        $lastId = $this->connection->lastInsertId();
        if ($lastId !== false) {
            $model->hydrate(['id' => $lastId] + $model->toArray());
        }
        return $rows;
    }

    public function update(AbstractModel $model, array $arguments = []): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->update($this->getTable());
        foreach ($model->toArray() as $property => $value) {
            $query->set($property, $query->createPositionalParameter($value));
        }
        $this->generateWhereQuery($query, $arguments);
        return $query->executeStatement();
    }

    /**
     * @param array $arguments
     * @param array<string,string> $orderBy
     * @param int|null $limit
     * @return QueryBuilder
     */
    protected function generateSelectQuery(array $arguments = [], array $orderBy = [], ?int $limit = null): QueryBuilder
    {
        $properties = $this->getProperties();
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$properties)
            ->from($this->getTable());
        $this->generateWhereQuery($query, $arguments);
        foreach ($orderBy as $property => $order) {
            $query->orderBy($property, $order);
        }
        $query->setMaxResults($limit);
        return $query;
    }

    protected function generateWhereQuery(QueryBuilder $query, array $arguments = []): void
    {
        foreach ($arguments as $property => $value) {
            if (is_array($value)) {
                $query->andWhere($query->expr()->in($property, $query->createPositionalParameter($value, Connection::PARAM_STR_ARRAY)));
                continue;
            }
            $query->andWhere($property . ' = ' . $query->createPositionalParameter($value));
        }
    }

    protected static function toObject(ReflectionClass $reflectionClass, array $data): AbstractModel
    {
        /**
         * @var AbstractModel $model
         */
        $model = $reflectionClass->newInstance();
        $model->hydrate($data);
        return $model;
    }
}