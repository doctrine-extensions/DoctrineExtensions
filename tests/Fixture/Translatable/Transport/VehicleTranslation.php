<?php

namespace Fixture\Translatable\Transport;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(name="post_translation_unique_idx", columns={"locale", "object_id"})
 * })
 */
class VehicleTranslation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="Vehicle", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $object;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    public function __construct($locale = null, $title = null)
    {
        $this->locale = $locale;
        $this->title = $title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
