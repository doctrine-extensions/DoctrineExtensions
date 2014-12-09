<?php
namespace Tree\Fixture\Issue1167;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
class Category
{
    /**
     * @MongoDB\Id(strategy="NONE")
     * @Gedmo\TreePathSource
     * @var string
     */
    private $id;

    /**
     * @MongoDB\String
     * @Gedmo\TreePath(appendId=false, separator="/")
     * @var string
     */
    private $path;


    /**
     * @MongoDB\Int
     * @Gedmo\TreeLevel
     * @var int
     */
    private $level;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Category", simple=true, inversedBy="children")
     * @Gedmo\TreeParent
     * @var Category
     */
    private $parent;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Category", mappedBy="parent")
     * @var Category[]
     */
    private $children = [];

    /**
     * @MongoDB\Date
     * @Gedmo\TreeLockTime
     * @var \DateTime
     */
    private $lockTime;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}