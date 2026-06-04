<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\FilterQException;
use Hyvor\FilterQ\FilterQ;
use Hyvor\FilterQ\Tests\TestCase;

class FilterQTest extends TestCase
{
    public function testEmpty(): void
    {
        $this->createPost(['id' => 1, 'slug' => 'hello']);
        $this->createPost(['id' => 2, 'slug' => 'world']);

        $qb = $this->createQueryBuilder();
        FilterQ::expression(null)->queryBuilder($qb)->addWhere();

        $result = $qb->getQuery()->getResult();
        $this->assertCount(2, $result);

        $qb2 = $this->createQueryBuilder();
        FilterQ::expression('')->queryBuilder($qb2)->addWhere();

        $result2 = $qb2->getQuery()->getResult();
        $this->assertCount(2, $result2);
    }

    public function testWithQueryBuilder(): void
    {
        $this->createPost(['id' => 1, 'slug' => 'hello']);
        $this->createPost(['id' => 2, 'slug' => 'world']);
        $this->createPost(['id' => 3, 'slug' => 'test']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('id=1|slug=world')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
                $keys->add('slug')->column('p.slug');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertContains(1, $this->getPostIds($result));
        $this->assertContains(2, $this->getPostIds($result));
    }

    public function testWithExistingConditions(): void
    {
        $this->createPost(['id' => 1, 'slug' => 'hello', 'status' => 'published']);
        $this->createPost(['id' => 2, 'slug' => 'world', 'status' => 'draft']);
        $this->createPost(['id' => 3, 'slug' => 'test', 'status' => 'published']);

        $qb = $this->createQueryBuilder()
            ->andWhere('p.status = :status')
            ->setParameter('status', 'published');

        $result = FilterQ::expression('id=1|id=2')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testJoin(): void
    {
        $author = $this->createAuthor(['id' => 1, 'name' => 'John']);
        $this->createPost(['id' => 1, 'slug' => 'post1', 'author' => $author]);
        $this->createPost(['id' => 2, 'slug' => 'post2']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression("author.name=John")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('author.name')
                    ->column('a.name')
                    ->join(function ($qb): void {
                        $qb->leftJoin('p.author', 'a');
                    });
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testJoinDeduplication(): void
    {
        $author = $this->createAuthor(['id' => 1, 'name' => 'John']);
        $this->createPost(['id' => 1, 'slug' => 'post1', 'author' => $author]);

        $qb = $this->createQueryBuilder();

        $joinCount = 0;
        $result = FilterQ::expression("author.name=John&author.name=John")
            ->queryBuilder($qb)
            ->keys(function ($keys) use (&$joinCount): void {
                $keys->add('author.name')
                    ->column('a.name')
                    ->join(function ($qb) use (&$joinCount): void {
                        $joinCount++;
                        $qb->leftJoin('p.author', 'a');
                    });
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertEquals(1, $joinCount, 'Join should only be added once');
        $this->assertCount(1, $result);
    }

    public function testJoinWithCallback(): void
    {
        $author = $this->createAuthor(['id' => 1, 'name' => 'Jane']);
        $this->createPost(['id' => 1, 'author' => $author]);
        $this->createPost(['id' => 2]);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression("author.name=Jane")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('author.name')
                    ->column('a.name')
                    ->join(function ($qb): void {
                        $qb->join('p.author', 'a');
                    });
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testCustomOperatorLike(): void
    {
        $this->createPost(['id' => 1, 'title' => 'Hello World']);
        $this->createPost(['id' => 2, 'title' => 'Goodbye World']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression("title~'Hello%'")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('title')->column('p.title');
            })
            ->operators(function ($operators): void {
                $operators->add('~', 'LIKE');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testCustomOperatorCallback(): void
    {
        $this->createPost(['id' => 1, 'title' => 'Hello World']);
        $this->createPost(['id' => 2, 'title' => 'Goodbye World']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression("title!world")
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('title')->column('p.title');
            })
            ->operators(function ($operators): void {
                $operators->add('!', function ($qb, string $paramName, mixed $value): string {
                    $qb->setParameter($paramName, '%' . $value . '%');
                    return 'p.title LIKE :' . $paramName;
                });
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
    }

    public function testExceptionAccessingRemovedOperator(): void
    {
        $this->expectException(FilterQException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id>20')
            ->queryBuilder($qb)
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

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id%20')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
            })
            ->addWhere();
    }

    public function testKeyOperatorsIncluding(): void
    {
        $this->expectException(FilterQException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id!=20')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')
                    ->column('p.id')
                    ->operators('=,>,<');
            })
            ->addWhere();
    }

    public function testKeyOperatorsIncludingArray(): void
    {
        $this->expectException(FilterQException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id!=20')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')
                    ->column('p.id')
                    ->operators(['=', '>']);
            })
            ->addWhere();
    }

    public function testKeyOperatorsExcluding(): void
    {
        $this->expectException(FilterQException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('id>20')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')
                    ->column('p.id')
                    ->operators('>', true);
            })
            ->addWhere();
    }

    public function test_nested_logic(): void
    {
        $this->createPost(['id' => 1, 'views' => 5]);
        $this->createPost(['id' => 2, 'views' => 5]);
        $this->createPost(['id' => 3, 'views' => 10]);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('((id=1&views=5)|(id=2))')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
                $keys->add('views')->column('p.views');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertContains(1, $this->getPostIds($result));
        $this->assertContains(2, $this->getPostIds($result));
    }

    public function testExceptionUnsupportedKey(): void
    {
        $this->expectException(FilterQException::class);

        $qb = $this->createQueryBuilder();
        FilterQ::expression('unknown=1')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('id')->column('p.id');
            })
            ->addWhere();
    }

    public function testNullEquality(): void
    {
        $this->createPost(['id' => 1, 'slug' => null]);
        $this->createPost(['id' => 2, 'slug' => 'hello']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('slug=null')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('slug')->column('p.slug');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testNullInequality(): void
    {
        $this->createPost(['id' => 1, 'slug' => null]);
        $this->createPost(['id' => 2, 'slug' => 'hello']);

        $qb = $this->createQueryBuilder();
        $result = FilterQ::expression('slug!=null')
            ->queryBuilder($qb)
            ->keys(function ($keys): void {
                $keys->add('slug')->column('p.slug');
            })
            ->addWhere()
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->id);
    }
}
