<?php

namespace Hyvor\FilterQ;

use Closure;
use Hyvor\FilterQ\Exceptions\FilterQException;

class Key
{
    private string $name;
    private string $column;
    private ?Closure $join = null;

    /** @var null|string[] */
    private ?array $includedOperators = null;

    /** @var null|string[] */
    private ?array $excludedOperators = null;

    /** @var null|mixed[] */
    private ?array $supportedValues = null;

    /** @var null|string[] */
    private ?array $supportedValueTypes = null;

    public function __construct(string $name)
    {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $name)) {
            throw new FilterQException("Invalid key name: $name");
        }

        $this->name = $name;
    }

    public function column(string $column): self
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @param string|string[] $operators
     */
    public function operators(string|array $operators, bool $exclude = false): self
    {
        if (is_string($operators)) {
            $operators = explode(',', $operators);
        }

        if ($exclude) {
            $this->excludedOperators = $operators;
        } else {
            $this->includedOperators = $operators;
        }
        return $this;
    }

    /**
     * @param mixed $values
     */
    public function values(mixed $values): self
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        $this->supportedValues = $values;
        return $this;
    }

    /**
     * @param string|string[] $type
     * @throws FilterQException
     */
    public function valueType(array|string $type): self
    {
        $types = is_string($type) ? explode('|', $type) : $type;

        foreach ($types as $t) {
            if (!in_array($t, ValueValidator::SUPPORTED_VALUES, true)) {
                throw new FilterQException("Key type $t is not supported");
            }
        }

        $this->supportedValueTypes = $types;
        return $this;
    }

    /**
     * Set a join callback. The callable receives the QueryBuilder and should add the join.
     */
    public function join(callable $join): self
    {
        $this->join = $join instanceof Closure ? $join : Closure::fromCallable($join);
        return $this;
    }

    public function getColumnName(): string
    {
        return $this->column ?? $this->name;
    }

    public function getJoin(): ?Closure
    {
        return $this->join;
    }

    /** @return null|string[] */
    public function getIncludedOperators(): ?array
    {
        return $this->includedOperators;
    }

    /** @return null|string[] */
    public function getExcludedOperators(): ?array
    {
        return $this->excludedOperators;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return mixed[]|null */
    public function getSupportedValues(): ?array
    {
        return $this->supportedValues;
    }

    /** @return string[]|null */
    public function getSupportedValueTypes(): ?array
    {
        return $this->supportedValueTypes;
    }
}
