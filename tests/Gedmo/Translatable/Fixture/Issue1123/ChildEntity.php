<?php

namespace Gedmo\Tests\Translatable\Fixture\Issue1123;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 * @ORM\Table("child_entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'child_entity')]
class ChildEntity extends BaseEntity implements Translatable
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="childTitle", type="string", length=128, nullable=true)
     */
    #[ORM\Column(name: 'childTitle', type: Types::STRING, length: 128, nullable: true)]
    #[Gedmo\Translatable]
    private $childTitle;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    #[Gedmo\Locale]
    private $locale = 'en';

    public function getChildTitle()
    {
        return $this->childTitle;
    }

    public function setChildTitle($childTitle)
    {
        $this->childTitle = $childTitle;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
