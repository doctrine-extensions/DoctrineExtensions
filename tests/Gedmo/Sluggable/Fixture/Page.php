<?php

namespace Sluggable\Fixture;

/**
 * @Entity
 */
class Page
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer") 
     */
    private $id;

    /**
     * @gedmo:Sluggable
     * @Column(type="string", length=255)
     */
    private $content;
    
    /**
     * @gedmo:Slug(style="camel", separator="_")
     * @Column(type="string", length=128)
     */
    private $slug;
    
    /**
     * @OneToMany(targetEntity="TranslatableArticle", mappedBy="page")
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