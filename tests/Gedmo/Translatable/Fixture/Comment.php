<?php

namespace Translatable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="subject", type="string", length=128)
     */
    private $subject;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    private $article;

    /**
     * Used locale to override Translation listener`s locale
     * @Gedmo\Language
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
