<?php

namespace Hyvor\FilterQ\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public int $id;

    #[ORM\Column(type: 'datetime')]
    public \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $slug = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $title = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $status = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $views = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    public ?bool $is_featured = null;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
    public ?Author $author = null;
}
