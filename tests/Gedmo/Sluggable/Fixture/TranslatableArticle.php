<?php

namespace Sluggable\Fixture;

use Gedmo\Sluggable\Sluggable,
    Gedmo\Translatable\Translatable;

/**
 * @Entity
 */
class TranslatableArticle implements Sluggable, Translatable
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer") 
     */
    private $id;

    /**
     * @gedmo:Translatable
     * @gedmo:Sluggable
     * @Column(type="string", length=64)
     */
    private $title;

    /**
     * @gedmo:Translatable
     * @gedmo:Sluggable
     * @Column(type="string", length=16)
     */
    private $code;
    
    /**
     * @gedmo:Translatable
     * @gedmo:Slug
     * @Column(type="string", length=128)
     */
    private $slug;
    
    /**
     * @OneToMany(targetEntity="Comment", mappedBy="article")
     */
    private $comments;
    
    /**
     * @ManyToOne(targetEntity="Page", inversedBy="articles")
     */
    private $page;
    
    /**
     * @gedmo:Locale
     * Used locale to override Translation listener`s locale
     */
    private $locale;

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }
    
    public function setPage($page)
    {
        $this->page = $page;
    }
    
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

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }
    
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}