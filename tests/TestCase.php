<?php

namespace Hyvor\FilterQ\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Hyvor\FilterQ\Tests\Entity\Author;
use Hyvor\FilterQ\Tests\Entity\Post;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected EntityManager $em;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/Entity'],
            isDevMode: true,
        );
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->em = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    protected function tearDown(): void
    {
        $this->em->close();
    }

    protected function createQueryBuilder(string $alias = 'p'): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from(Post::class, $alias);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createPost(array $data): Post
    {
        $post = new Post();
        foreach ($data as $key => $value) {
            $post->$key = $value;
        }
        $this->em->persist($post);
        $this->em->flush();
        return $post;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createAuthor(array $data): Author
    {
        $author = new Author();
        foreach ($data as $key => $value) {
            $author->$key = $value;
        }
        $this->em->persist($author);
        $this->em->flush();
        return $author;
    }

    /**
     * @param Post[] $posts
     * @return int[]
     */
    protected function getPostIds(array $posts): array
    {
        return array_map(fn(Post $p) => $p->id, $posts);
    }
}
