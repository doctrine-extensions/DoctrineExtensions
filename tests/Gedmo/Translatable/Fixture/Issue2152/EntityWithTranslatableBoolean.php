<?php

namespace Translatable\Fixture\Issue2152;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table("entity")
 */
class EntityWithTranslatableBoolean
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $isOperating;

    /**
     * @var string
     *
     * @Gedmo\Locale()
     */
    private $locale;

    /**
     * @param string        $title
     * @param string|null $isOperating
     */
    public function __construct($title, $isOperating = '0')
    {
        $this->translateInLocale('en', $title, $isOperating);
    }

    public function translateInLocale($locale, $title, $isOperating)
    {
        $this->title = $title;
        $this->isOperating = $isOperating;
        $this->locale = $locale;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function isOperating()
    {
        return $this->isOperating;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
