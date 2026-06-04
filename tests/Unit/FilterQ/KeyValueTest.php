<?php

namespace Hyvor\FilterQ\Tests\Unit\FilterQ;

use Hyvor\FilterQ\Exceptions\InvalidValueException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class KeyValueTest extends TestCase
{
    public function test_key_value(): void
    {
        $this->createPost(['id' => 200, 'slug' => 'hello']);
        $this->createPost(['id' => 300, 'slug' => 'world']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('id=200')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->values(200);
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(200, $result[0]->id);
    }

    public function test_key_value_invalid(): void
    {
        $this->expectException(InvalidValueException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id=200')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->values(300);
            })
            ->addWhere();
    }

    public function test_key_values(): void
    {
        $this->createPost(['id' => 200]);
        $this->createPost(['id' => 300]);
        $this->createPost(['id' => 400]);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('id=200|id=300')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->values([200, 300]);
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertContains(200, $this->getPostIds($result));
        $this->assertContains(300, $this->getPostIds($result));
    }

    public function test_key_values_invalid(): void
    {
        $this->expectException(InvalidValueException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id=200|id=300')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->values([200, 400]);
            })
            ->addWhere();
    }

    public function test_key_invalid_values_one_among_many(): void
    {
        $this->expectException(InvalidValueException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id=200|slug=photo')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->values([200, 400]);
                $keys->add('slug')->column('p.slug')->values(['audio', 'type']);
            })
            ->addWhere();
    }
}
