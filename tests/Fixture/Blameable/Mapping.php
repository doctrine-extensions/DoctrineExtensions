<?php

namespace Fixture\Blameable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Mapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @ORM\Column(type="boolean")
     */
    private $published = false;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\Column
     */
    private $createdBy;

    /**
     * @ORM\Column
     * @Gedmo\Blameable
     */
    private $updatedBy;

    /**
     * @ORM\Column
     * @Gedmo\Blameable(on="change", field={"title", "body"})
     */
    private $changedBy;

    /**
     * @ORM\Column(nullable=true)
     * @Gedmo\Blameable(on="change", field="published", value=true)
     */
    private $publishedBy;
}
