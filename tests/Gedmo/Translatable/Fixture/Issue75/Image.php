<?php

namespace Translatable\Fixture\Issue75;

/**
 * @Entity
 */
class Image
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @gedmo:Translatable
     * @Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ManyToMany(targetEntity="Article", mappedBy="images")
     */
    private $articles;

    public function getId()
    {
        return $this->id;
    }

    public function addArticle(Article $article)
    {
        $this->articles[] = $article;
    }

    public function getArticles()
    {
        return $this->articles;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}