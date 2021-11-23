<?php

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

class BaseCategory
{
    /**
     * @var int
     */
    private $left;

    /**
     * @var int
     */
    private $right;

    /**
     * @var int
     */
    private $level;

    /**
     * @var int
     */
    private $rooted;

    /**
     * @var \DateTime|null
     */
    private $created;

    /**
     * @var \DateTime|null
     */
    private $updated;

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return \DateTime $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setRooted($rooted)
    {
        $this->rooted = $rooted;

        return $this;
    }

    public function getRooted()
    {
        return $this->rooted;
    }
}
