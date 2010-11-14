<?php

namespace Translatable\Fixture;

/**
 * @Entity
 */
class Comment
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @gedmo:Translatable
     * @Column(name="subject", type="string", length=128)
     */
    private $subject;

    /**
     * @gedmo:Translatable
     * @Column(name="message", type="text")
     */
    private $message;
    
    /**
     * @ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    private $article;
    
    /**
     * Used locale to override Translation listener`s locale
     * @gedmo:Language
     */
    private $locale;

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
    
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
