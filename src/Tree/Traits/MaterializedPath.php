<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * MaterializedPath Trait
 *
 * @author Steffen Roßkamp <steffen.rosskamp@gimmickmedia.de>
 */
trait MaterializedPath
{
    /**
     * @var string
     */
    protected $path;
    /**
     * @var self
     */
    protected $parent;
    /**
     * @var int
     */
    protected $level;
    /**
     * @var Collection|self[]
     */
    protected $children;
    /**
     * @var string
     */
    protected $hash;

    /**
     * @param self $parent
     *
     * @return self
     */
    public function setParent(self $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return self
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param string $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $hash
     *
     * @return self
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param Collection|self[] $children
     *
     * @return self
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren()
    {
        return $this->children = $this->children ?: new ArrayCollection();
    }
}
