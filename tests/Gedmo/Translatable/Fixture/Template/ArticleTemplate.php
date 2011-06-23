<?php

namespace Translatable\Fixture\Template;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class ArticleTemplate
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * Used locale to override Translation listener`s locale
     * @Gedmo\Locale
     */
    protected $locale;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}