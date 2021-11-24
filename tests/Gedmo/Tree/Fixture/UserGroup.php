<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group entity
 *
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user_group")
 */
class UserGroup extends Role
{
    /**
     * @ORM\Column(name="name", type="string", length=191)
     *
     * @var string
     */
    private $name;

    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * @return string
     */
    public function getRoleId()
    {
        return $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->setRoleId($name);

        return $this;
    }
}
