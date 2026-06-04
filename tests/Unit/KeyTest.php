<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\FilterQException;
use Hyvor\FilterQ\Key;
use Hyvor\FilterQ\Tests\TestCase;

class KeyTest extends TestCase
{
    public function test_key_name(): void
    {
        $this->expectException(FilterQException::class);
        new Key('(E*@NCQLK');
    }

    public function test_key_column(): void
    {
        $key = new Key('test');
        $key->column('p.age');

        $this->assertEquals('p.age', $key->getColumnName());
    }

    public function test_key_column_string_expression(): void
    {
        $key = new Key('test');
        $key->column('COUNT(p.id)');

        $this->assertEquals('COUNT(p.id)', $key->getColumnName());
    }

    public function test_key_operators(): void
    {
        $key = new Key('test');
        $key->operators('>,<');

        $this->assertEquals(['>', '<'], $key->getIncludedOperators());
    }

    public function test_key_operators_array(): void
    {
        $key = new Key('test');
        $key->operators(['>', '<']);

        $this->assertEquals(['>', '<'], $key->getIncludedOperators());
    }

    public function test_key_values(): void
    {
        $key = new Key('test');
        $key->values(200);

        $this->assertEquals([200], $key->getSupportedValues());
    }

    public function test_key_values_array(): void
    {
        $key = new Key('test');
        $key->values([1, 2]);

        $this->assertEquals([1, 2], $key->getSupportedValues());
    }

    public function test_key_value_type(): void
    {
        $key = new Key('test');
        $key->valueType('string');

        $this->assertEquals(['string'], $key->getSupportedValueTypes());
    }

    public function test_key_value_type_multi(): void
    {
        $key = new Key('test');
        $key->valueType('string|null');

        $this->assertEquals(['string', 'null'], $key->getSupportedValueTypes());
    }

    public function test_key_value_type_wrong(): void
    {
        $this->expectException(FilterQException::class);

        $key = new Key('test');
        $key->valueType('stringify');
    }

    public function test_join(): void
    {
        $key = new Key('test');
        $key->join(function ($qb): void {
            $qb->leftJoin('p.author', 'a');
        });

        $qb1 = $this->createQueryBuilder()->leftJoin('p.author', 'a');
        $qb2 = $this->createQueryBuilder();

        $joinFunc = $key->getJoin();
        $this->assertNotNull($joinFunc);
        $joinFunc($qb2);

        $this->assertEquals($qb1->getDQL(), $qb2->getDQL());
    }

    public function test_join_callback(): void
    {
        $key = new Key('test');
        $key->join(function ($qb): void {
            $qb->join('p.author', 'a');
        });

        $qb1 = $this->createQueryBuilder()->join('p.author', 'a');
        $qb2 = $this->createQueryBuilder();

        $joinFunc = $key->getJoin();
        $this->assertNotNull($joinFunc);
        $joinFunc($qb2);

        $this->assertEquals($qb1->getDQL(), $qb2->getDQL());
    }

    public function test_chaining(): void
    {
        $key = new Key('test');

        $keyNew = $key
            ->column('p.test')
            ->values('test')
            ->valueType('string')
            ->join(function (): void {})
            ->operators('>');

        $this->assertEquals($key, $keyNew);
    }
}
