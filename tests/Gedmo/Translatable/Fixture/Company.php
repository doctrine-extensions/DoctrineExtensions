<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
class Company implements Translatable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     * @Gedmo\Translatable
     */
    private $title;

    /**
     * @var CompanyEmbedLink
     * @ORM\Embedded(class="Gedmo\Tests\Translatable\Fixture\CompanyEmbedLink")
     */
    private $link;

    /**
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    private $locale;

    public function __construct()
    {
        $this->link = new CompanyEmbedLink();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return Company
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return CompanyEmbedLink
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     *
     * @return Company
     */
    public function setLink(CompanyEmbedLink $link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @param mixed $locale
     *
     * @return Company
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
