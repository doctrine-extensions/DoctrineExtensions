<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class StringIdentifier
{
    /**
     * @ORM\Id
     * @ORM\Column(name="uid", type="string", length=32)
     */
    #[ORM\Id]
    #[ORM\Column(name: 'uid', type: Types::STRING, length: 32)]
    private $uid;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    private $locale;

    public function getUid()
    {
        return $this->uid;
    }

    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
