<?php

namespace Sortable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Paper
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Author", mappedBy="paper", cascade={"persist", "remove"})
     */
    private $authors;

    public function __construct() { $this->authors = new ArrayCollection(); }
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }
    public function getAuthors() { return $this->authors; }
    public function addAuthor($author) { $this->authors->add($author); }
}
