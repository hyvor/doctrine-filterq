<?php

namespace Hyvor\FilterQ\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'authors')]
class Author
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    /** @var Collection<int, Post> */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
    public Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }
}
