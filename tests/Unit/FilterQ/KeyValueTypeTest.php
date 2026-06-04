<?php

namespace Hyvor\FilterQ\Tests\Unit\FilterQ;

use Hyvor\FilterQ\Exceptions\InvalidValueException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class KeyValueTypeTest extends TestCase
{
    public function test_key_type_int_check(): void
    {
        $filterQ = FilterQ::expression("id=2")
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->valueType('int');
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->andWhere('p.id = :id')
            ->setParameter('id', 2)
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function test_key_type_int_invalid_check(): void
    {
        $this->expectException(InvalidValueException::class);

        FilterQ::expression("id='2'")
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->valueType('int');
            })
            ->addWhere();
    }

    public function test_key_type_date(): void
    {
        $filterQ = FilterQ::expression("created_at='2022-02-22'")
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('created_at')->column('p.id')->valueType('date');
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->andWhere('p.id = :created_at')
            ->setParameter('created_at', new \DateTimeImmutable('2022-02-22'))
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }
}
