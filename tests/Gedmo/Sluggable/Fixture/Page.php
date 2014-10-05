<?php

namespace Sluggable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $content;

    /**
     * @Gedmo\Slug(style="camel", separator="_", fields={"content"})
     * @ORM\Column(type="string", length=128)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="TranslatableArticle", mappedBy="page")
     */
    private $articles;

    public function getId()
    {
        return $this->id;
    }

    public function addArticle(TranslatableArticle $article)
    {
        $article->setPage($this);
        $this->articles[] = $article;
    }

    public function getArticles()
    {
        return $this->articles;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
