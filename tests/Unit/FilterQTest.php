<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\FilterQException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class FilterQTest extends TestCase
{
    public function testEmpty(): void
    {
        $filterQ = FilterQ::expression(null)
            ->queryBuilder($this->createQueryBuilder())
            ->addWhere()
            ->getQuery();

        $filterQ2 = FilterQ::expression('')
            ->queryBuilder($this->createQueryBuilder())
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
        $this->assertSame($q->getSQL(), $filterQ2->getSQL());
    }

    public function testWithQueryBuilder(): void
    {
        $filterQ = FilterQ::expression('id=1|slug=hello')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
                $keys->add('slug')->column('p.slug');
            })
            ->addWhere()
            ->getQuery();

        $qb = $this->createQueryBuilder();
        $q = $qb
            ->andWhere($qb->expr()->orX('p.id = :id', 'p.slug = :slug'))
            ->setParameter('id', 1)
            ->setParameter('slug', 'hello')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testWithExistingQueryBuilder(): void
    {
        $filterQ = FilterQ::expression('id=1|slug=hello')
            ->queryBuilder(
                $this->createQueryBuilder()
                    ->andWhere('p.status = :status')
                    ->setParameter('status', 'published')
            )
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
                $keys->add('slug')->column('p.slug');
            })
            ->addWhere()
            ->getQuery();

        $qb = $this->createQueryBuilder()
            ->andWhere('p.status = :status')
            ->setParameter('status', 'published');
        $q = $qb
            ->andWhere($qb->expr()->orX('p.id = :id', 'p.slug = :slug'))
            ->setParameter('id', 1)
            ->setParameter('slug', 'hello')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testJoin(): void
    {
        $filterQ = FilterQ::expression('author.name=test')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('author.name')
                    ->column('a.name')
                    ->join(function ($qb): void {
                        $qb->leftJoin('p.author', 'a');
                    });
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->leftJoin('p.author', 'a')
            ->andWhere('a.name = :name')
            ->setParameter('name', 'test')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testJoinWithCallback(): void
    {
        $filterQ = FilterQ::expression('author.name=test')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('author.name')
                    ->column('a.name')
                    ->join(function ($qb): void {
                        $qb->join('p.author', 'a');
                    });
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->join('p.author', 'a')
            ->andWhere('a.name = :name')
            ->setParameter('name', 'test')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testCustomOperatorLike(): void
    {
        $filterQ = FilterQ::expression("title~'Hello%'")
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('title')->column('p.title');
            })
            ->operators(function ($operators): void {
                $operators->add('~', 'LIKE');
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->andWhere('p.title LIKE :title')
            ->setParameter('title', 'Hello%')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testCustomOperatorCallback(): void
    {
        $filterQ = FilterQ::expression("title!hello")
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('title')->column('p.title');
            })
            ->operators(function ($operators): void {
                $operators->add('!', function ($qb, string $paramName, mixed $value): string {
                    $qb->setParameter($paramName, $value);
                    return 'LOWER(p.title) = :' . $paramName;
                });
            })
            ->addWhere()
            ->getQuery();

        $q = $this->createQueryBuilder()
            ->andWhere('LOWER(p.title) = :title')
            ->setParameter('title', 'hello')
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }

    public function testExceptionAccessingRemovedOperator(): void
    {
        $this->expectException(FilterQException::class);

        FilterQ::expression('id>20')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
            })
            ->operators(function ($operators): void {
                $operators->remove('>');
            })
            ->addWhere();
    }

    public function testExceptionAccessingInvalidOperator(): void
    {
        $this->expectException(FilterQException::class);

        FilterQ::expression('id%20')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
            })
            ->addWhere();
    }

    public function testKeyOperatorsIncluding(): void
    {
        $this->expectException(FilterQException::class);

        FilterQ::expression('id!=20')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->operators('=,>,<');
            })
            ->addWhere();
    }

    public function testKeyOperatorsIncludingArray(): void
    {
        $this->expectException(FilterQException::class);

        FilterQ::expression('id!=20')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->operators(['=', '>']);
            })
            ->addWhere();
    }

    public function testKeyOperatorsExcluding(): void
    {
        $this->expectException(FilterQException::class);

        FilterQ::expression('id>20')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id')->operators('>', true);
            })
            ->addWhere();
    }

    public function test_nested_logic(): void
    {
        $filterQ = FilterQ::expression('((id=1&views=5)|(id=2))')
            ->queryBuilder($this->createQueryBuilder())
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
                $keys->add('views')->column('p.views');
            })
            ->addWhere()
            ->getQuery();

        $qb = $this->createQueryBuilder();
        $q = $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX('p.id = :id1', 'p.views = :views'),
                    'p.id = :id2'
                )
            )
            ->setParameter('id1', 1)
            ->setParameter('views', 5)
            ->setParameter('id2', 2)
            ->getQuery();

        $this->assertSame($q->getSQL(), $filterQ->getSQL());
    }
}
