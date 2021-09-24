<?php

namespace Gedmo\Tests\Translatable\Fixture\Issue114;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category", cascade={"persist", "remove"})
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private $articles;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function addArticle(Article $article)
    {
        $this->articles[] = $article;
    }

    public function getArticles()
    {
        return $this->articles;
    }
}
