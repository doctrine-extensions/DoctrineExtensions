<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

class BaseCategory
{
    private ?int $left = null;

    private ?int $right = null;

    private ?int $level = null;

    private ?int $rooted = null;

    private ?\DateTime $created = null;

    private ?\DateTime $updated = null;

    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime $created
     */
    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setUpdated(\DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setLeft(int $left): self
    {
        $this->left = $left;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setRight(int $right): self
    {
        $this->right = $right;

        return $this;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setRooted(int $rooted): self
    {
        $this->rooted = $rooted;

        return $this;
    }

    public function getRooted(): int
    {
        return $this->rooted;
    }
}
