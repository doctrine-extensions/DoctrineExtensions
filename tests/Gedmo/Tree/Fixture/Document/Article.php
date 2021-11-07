<?php

namespace Gedmo\Tests\Tree\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MONGO;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MONGO\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
class Article
{
    /**
     * @MONGO\Id
     */
    private $id;

    /**
     * @MONGO\Field(type="string")
     * @Gedmo\TreePathSource
     */
    private $title;

    /**
     * @MONGO\Field(type="string")
     * @Gedmo\TreePath(separator="|")
     */
    private $path;

    /**
     * @Gedmo\TreeParent
     * @MONGO\ReferenceOne(targetDocument="Article")
     */
    private $parent;

    /**
     * @Gedmo\TreeLevel
     * @MONGO\Field(type="int")
     */
    private $level;

    /**
     * @Gedmo\TreeLockTime
     * @MONGO\Field(type="date")
     */
    private $lockTime;

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

    public function setParent(Article $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }
}
