<?php
/**
 * @author Anatoly Marinescu <tolean@zingan.com>
 * @copyright 2012 Zingan.com SRL. www.zingan.com
 * @since 2012-02-29
 */

namespace Tree\Fixture\Closure;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="Tree\Fixture\Closure\ResourceClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class Resource
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
     * @ORM\ManyToOne(targetEntity="Resource", inversedBy="children")
     */
    private $parent;

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

    public function setParent(Resource $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
