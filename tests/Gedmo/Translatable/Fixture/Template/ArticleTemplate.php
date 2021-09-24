<?php

namespace Gedmo\Tests\Translatable\Fixture\Template;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
class ArticleTemplate
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="content", type="text")
     */
    #[ORM\Column(name: 'content', type: Types::TEXT)]
    #[Gedmo\Translatable]
    private $content;

    /**
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
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
