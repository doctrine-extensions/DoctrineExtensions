<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Comment
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
     * @ORM\Column(name="subject", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'subject', type: Types::STRING, length: 128)]
    private $subject;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="message", type="text")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private $article;

    /**
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Language
     */
    #[Gedmo\Language]
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
