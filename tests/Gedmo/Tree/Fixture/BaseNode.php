<?php

namespace Tree\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({"base" = "BaseNode", "node" = "Node"})
 * @Gedmo\Tree(type="nested")
 */
class BaseNode extends ANode
{
    /**
     * @ORM\OneToMany(targetEntity="BaseNode", mappedBy="parent")
     */
    private $children;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(length=128, unique=true)
     */
    private $identifier;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable
     */
    private $updated;

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
