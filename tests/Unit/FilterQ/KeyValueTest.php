<?php

namespace Hyvor\FilterQ\Tests\Unit\FilterQ;

use Hyvor\FilterQ\Exceptions\InvalidValueException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class KeyValueTest extends TestCase
{
    public function test_key_value(): void
    {
        $filterQ = FilterQ::expression('id=200')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id', 'p.id')->values(200);
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->andWhere('p.id = :id')
            ->setParameter('id', 200)
            ->getQuery();

        $this->assertSameQuery($q, $filterQ);
    }

    public function test_key_value_invalid(): void
    {
        $this->expectException(InvalidValueException::class);

        FilterQ::expression('id=200')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id', 'p.id')->values(300);
            })
            ->addWhere();
    }

    public function test_key_values(): void
    {
        $filterQ = FilterQ::expression('id=200|id=300')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id', 'p.id')->values([200, 300]);
            })
            ->addWhere()
            ->getQuery();

        $qb = $this->createQueryBuilder();
        $q = $qb
            ->andWhere($qb->expr()->orX('p.id = :id1', 'p.id = :id2'))
            ->setParameter('id1', 200)
            ->setParameter('id2', 300)
            ->getQuery();

        $this->assertSameQuery($q, $filterQ);
    }

    public function test_key_values_invalid(): void
    {
        $this->expectException(InvalidValueException::class);

        FilterQ::expression('id=200|id=300')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id', 'p.id')->values([200, 400]);
            })
            ->addWhere();
    }

    public function test_key_invalid_values_one_among_many(): void
    {
        $this->expectException(InvalidValueException::class);

        FilterQ::expression('id=200|slug=photo')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id', 'p.id')->values([200, 400]);
                $keys->add('slug', 'p.slug')->values(['audio', 'type']);
            })
            ->addWhere();
    }
}
