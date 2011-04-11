<?php

namespace Tree\Fixture;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({"base" = "BaseNode", "node" = "Node"})
 * @gedmo:Tree(type="nested")
 */
class BaseNode extends ANode
{
    /**
     * @gedmo:TreeParent
     * @ManyToOne(targetEntity="BaseNode", inversedBy="children")
     * @JoinColumns({
     *   @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $parent;

    /**
     * @OneToMany(targetEntity="BaseNode", mappedBy="parent")
     */
    private $children;

    /**
     * @gedmo:Timestampable(on="create")
     * @Column(type="datetime")
     */
    private $created;

    /**
     * @Column(length=128, unique=true)
     */
    private $identifier;

    /**
     * @Column(type="datetime")
     * @gedmo:Timestampable
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

    public function setParent($parent = null)
    {
        $this->parent = $parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getParent()
    {
        return $this->parent;
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