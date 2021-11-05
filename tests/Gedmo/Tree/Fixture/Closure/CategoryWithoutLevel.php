<?php

namespace Gedmo\Tests\Tree\Fixture\Closure;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevelClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class CategoryWithoutLevel
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="CategoryWithoutLevel", inversedBy="children")
     */
    private $parent;

    /**
     * @var Collection<int, CategoryWithoutLevelClosure>
     */
    private $closures;

    public function __construct()
    {
        $this->closures = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setParent(self $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function addClosure(CategoryWithoutLevelClosure $closure)
    {
        $this->closures[] = $closure;
    }
}
