<?php

namespace Translatable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class StringIdentifier
{
    /**
     * @ORM\Id
     * @ORM\Column(name="uid", type="string", length=32)
     */
    private $uid;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * Used locale to override Translation listener`s locale
     * @Gedmo\Locale
     */
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