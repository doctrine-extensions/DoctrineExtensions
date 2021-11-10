<?php

namespace Gedmo\Tests\Translatable\Fixture\Issue1123;

use Doctrine\DBAL\Types\Types;
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
#[ORM\Entity]
#[ORM\Table(name: 'base_entity')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: Types::STRING)]
#[ORM\DiscriminatorMap(['base' => BaseEntity::class, 'child' => ChildEntity::class])]
abstract class BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;
}
