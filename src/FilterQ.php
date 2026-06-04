<?php

namespace Hyvor\FilterQ;

use Closure;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Hyvor\FilterQ\Exceptions\FilterQException;

class FilterQ
{
    private string $filterExpression = '';
    private QueryBuilder $queryBuilder;
    private Operators $operators;
    private Keys $keys;

    /** @var string[] */
    private array $joinedKeys = [];

    private int $paramCounter = 0;

    public function __construct()
    {
        $this->keys = new Keys();
        $this->operators = new Operators();
    }

    public static function expression(?string $expression): self
    {
        $instance = new self();
        $instance->filterExpression = trim($expression ?? '');
        return $instance;
    }

    public function queryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    public function keys(Closure $closure): self
    {
        $closure($this->keys);
        return $this;
    }

    public function operators(Closure $closure): self
    {
        $closure($this->operators);
        return $this;
    }

    public function addWhere(): QueryBuilder
    {
        if (empty($this->filterExpression)) {
            return $this->queryBuilder;
        }

        $parsed = Parser::parse($this->filterExpression);
        $expr = $this->buildExpression($this->queryBuilder, $parsed);
        $this->queryBuilder->andWhere($expr);

        return $this->queryBuilder;
    }

    /**
     * @param array<string, mixed> $logicChunk
     * @throws FilterQException
     */
    private function buildExpression(QueryBuilder $qb, array $logicChunk): Expr\Composite
    {
        $type = array_key_exists('or', $logicChunk) ? 'or' : 'and';
        /** @var list<mixed> $items */
        $items = $logicChunk[$type];

        /** @var list<Expr\Composite|Expr\Comparison|Expr\Func|string> $parts */
        $parts = [];

        foreach ($items as $item) {
            /** @var array<mixed> $item */
            if (isset($item['and']) || isset($item['or'])) {
                /** @var array<string, mixed> $item */
                $parts[] = $this->buildExpression($qb, $item);
                continue;
            }

            /** @var array{0: string, 1: string, 2: mixed} $item */
            $key = (string) $item[0];
            $operator = (string) $item[1];
            $value = $item[2];

            $keyInst = $this->keys->get($key);
            if ($keyInst === null) {
                throw new FilterQException("Key '$key' is not supported for filtering");
            }

            $value = ValueValidator::validate($keyInst, $value);
            $column = $keyInst->getColumnName();

            $sqlOperator = $this->operators->get($operator);
            if ($sqlOperator === null) {
                throw new FilterQException("Operator '$operator' not supported for filtering");
            }

            $includedOperators = $keyInst->getIncludedOperators();
            $excludedOperators = $keyInst->getExcludedOperators();
            if (
                ($includedOperators !== null && !in_array($operator, $includedOperators, true)) ||
                ($excludedOperators !== null && in_array($operator, $excludedOperators, true))
            ) {
                throw new FilterQException("Operator '$operator' is not allowed for filtering (with $key)");
            }

            $join = $keyInst->getJoin();
            if ($join !== null && !in_array($key, $this->joinedKeys, true)) {
                $join($this->queryBuilder);
                $this->joinedKeys[] = $key;
            }

            if ($sqlOperator instanceof Closure) {
                $paramName = 'fq_' . ($this->paramCounter++);
                /** @var string $exprResult */
                $exprResult = $sqlOperator($qb, $paramName, $value);
                $parts[] = $exprResult;
            } elseif ($value === null) {
                if ($operator === '=') {
                    $parts[] = $qb->expr()->isNull($column);
                } elseif ($operator === '!=') {
                    $parts[] = $qb->expr()->isNotNull($column);
                } else {
                    throw new FilterQException("Operator '$operator' cannot be used with null values");
                }
            } else {
                $paramName = 'fq_' . ($this->paramCounter++);
                $qb->setParameter($paramName, $value);
                $parts[] = new Expr\Comparison($column, $sqlOperator, ':' . $paramName);
            }
        }

        if ($type === 'and') {
            return $qb->expr()->andX(...$parts);
        } else {
            return $qb->expr()->orX(...$parts);
        }
    }
}
