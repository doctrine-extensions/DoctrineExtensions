<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\MappedSuperclass]
class BaseCategory
{
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private ?int $left = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private ?int $right = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeRoot]
    private ?int $rooted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTime $created = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
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
