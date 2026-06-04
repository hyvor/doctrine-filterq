<?php

namespace Hyvor\FilterQ\Tests\Unit\FilterQ;

use Hyvor\FilterQ\Exceptions\InvalidValueException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class KeyValueTypeTest extends TestCase
{
    public function test_key_type_int_check(): void
    {
        $this->createPost(['id' => 2]);
        $this->createPost(['id' => 3]);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression("id=2")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->valueType('int');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->id);
    }

    public function test_key_type_int_invalid_check(): void
    {
        $this->expectException(InvalidValueException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression("id='2'")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->valueType('int');
            })
            ->addWhere();
    }

    public function test_key_type_date(): void
    {
        $qb = $this->createQueryBuilder();
        FilterQ::expression("id='2022-02-22'")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->valueType('date');
            })
            ->addWhere();

        $this->assertTrue(true);
    }
}
