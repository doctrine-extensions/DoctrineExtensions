<?php

namespace Translatable\Fixture;

use DoctrineExtensions\Translatable\Translatable;

/**
 * @Entity
 */
class Comment implements Translatable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="subject", type="string", length=128)
     */
    private $subject;

    /**
     * @Column(name="message", type="text")
     */
    private $message;
    
    /**
     * @ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    private $article;
    
    /*
     * Used locale to override Translation listener`s locale
     */
    private $_locale;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
    
    public function getTranslatableFields()
    {
        return array('subject', 'message');
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->_locale = $locale;
    }
    
    public function getTranslatableLocale()
    {
        return $this->_locale;
    }
    
    public function getTranslationEntity()
    {
        return null;
    }
}
