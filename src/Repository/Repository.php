<?php

namespace AlphaSoft\Sql\Repository;

use AlphaSoft\DataModel\AbstractModel;
use AlphaSoft\DataModel\Helper\ModelHelper;
use AlphaSoft\Sql\DoctrineManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class Repository
{
    /**
     * @var DoctrineManager
     */
    protected $manager;

    public function __construct(DoctrineManager $manager)
    {
        $this->manager = $manager;
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
        return ModelHelper::toObject($this->getModelName(), $item);
    }

    public function findBy(array $arguments = [], array $orderBy = [], ?int $limit = null): array
    {
        $query = $this->generateSelectQuery($arguments, $orderBy, $limit);
        $data = $query->fetchAllAssociative();

        return ModelHelper::toCollectionObject($this->getModelName(), $data);
    }

    public function insert(AbstractModel $model): int
    {
        $connection = $this->manager->getConnection();
        $query = $connection->createQueryBuilder();
        $query->insert($this->getTable());
        foreach ($model->toArray() as $property => $value) {
            self::setValue($query, $property, $value);
        }
        $rows = $query->executeStatement();
        $lastId = $connection->lastInsertId();
        if ($lastId !== false) {
            $model->hydrate(['id' => $lastId] + $model->toArray());
        }
        return $rows;
    }

    public function update(AbstractModel $model, array $arguments = []): int
    {
        $query = $this->createQueryBuilder();
        $query->update($this->getTable());
        foreach ($model->toArray() as $property => $value) {
            self::setValue($query, $property, $value);
        }
        self::generateWhereQuery($query, $arguments);
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
        $query = $this->createQueryBuilder();
        $query
            ->select(...$properties)
            ->from($this->getTable());
        self::generateWhereQuery($query, $arguments);
        foreach ($orderBy as $property => $order) {
            $query->orderBy($property, $order);
        }
        $query->setMaxResults($limit);
        return $query;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->manager->getConnection()->createQueryBuilder();
    }

    protected static function generateWhereQuery(QueryBuilder $query, array $arguments = []): void
    {
        foreach ($arguments as $property => $value) {
            if (is_array($value)) {
                $query->andWhere($query->expr()->in($property, $query->createPositionalParameter($value, Connection::PARAM_STR_ARRAY)));
                continue;
            }
            $query->andWhere($property . ' = ' . $query->createPositionalParameter($value));
        }
    }

    protected static function setValue(QueryBuilder $query, $property, $value): void
    {
        $type = ParameterType::STRING;
        if (\is_bool($value)) {
            $type = ParameterType::BOOLEAN;
        } elseif (\is_int($value)) {
            $type = ParameterType::INTEGER;
        } elseif (\is_null($value)) {
            $type = ParameterType::NULL;
        }
        $query->set($property, $query->createPositionalParameter($value, $type));
    }
}
