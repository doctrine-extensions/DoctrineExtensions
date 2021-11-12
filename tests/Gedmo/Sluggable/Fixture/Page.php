<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
     * @ORM\Column(type="string", length=191)
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
