<?php

namespace Hyvor\FilterQ;

use Closure;

class Operators
{
    /** @var array<string, string|Closure> */
    private array $operators = [];

    public function __construct()
    {
        $this->add('=');
        $this->add('!=');
        $this->add('<');
        $this->add('>');
        $this->add('<=');
        $this->add('>=');
    }

    public function add(string $operator, null|string|Closure $sqlOperator = null): self
    {
        $this->operators[$operator] = $sqlOperator ?? $operator;
        return $this;
    }

    public function remove(string $operator): self
    {
        if (isset($this->operators[$operator])) {
            unset($this->operators[$operator]);
        }
        return $this;
    }

    public function get(string $operator): string|Closure|null
    {
        return $this->operators[$operator] ?? null;
    }
}
