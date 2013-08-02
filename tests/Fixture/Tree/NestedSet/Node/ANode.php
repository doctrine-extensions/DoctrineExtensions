<?php

namespace Fixture\Tree\NestedSet\Node;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class ANode
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="BaseNode", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    public function getId()
    {
        return $this->id;
    }

    public function setParent($parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
