<?php

namespace Translatable\Fixture\Issue1123;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("base_entity")
 * @ORM\Inheritancetype("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap
 * ({
 * "base" = "BaseEntity",
 * "child" = "ChildEntity"
 * })
 */
abstract class BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;
}
