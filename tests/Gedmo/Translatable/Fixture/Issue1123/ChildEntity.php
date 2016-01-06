<?php

namespace Translatable\Fixture\Issue1123;

use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Translatable\Fixture\Issue1123\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table("child_entity")
 */
class ChildEntity extends BaseEntity implements Translatable
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="childTitle", type="string", length=128, nullable=true)
     */
    private $childTitle;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale = 'en';

    public function getChildTitle() {
        return $this->childTitle;
    }

    public function setChildTitle($childTitle) {
        $this->childTitle = $childTitle;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
