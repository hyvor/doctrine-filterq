<?php

namespace Hyvor\FilterQ;

class Keys
{
    /** @var array<string, Key> */
    public array $keys = [];

    public function add(string $name, string $column): Key
    {
        $key = new Key($name, $column);
        $this->keys[$name] = $key;
        return $key;
    }

    public function get(string $name): ?Key
    {
        return $this->keys[$name] ?? null;
    }
}
