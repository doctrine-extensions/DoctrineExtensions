<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class Author
{
    use SoftDeleteableEntity;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $firstname = null;

    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $lastname = null;

    /**
     * @var Collection<int, Article>
     *
     * @ORM\OneToMany(targetEntity="Article", mappedBy="author", cascade={"persist"})
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'author', cascade: ['persist'])]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function addArticle(Article $article): void
    {
        $this->articles[] = $article;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
