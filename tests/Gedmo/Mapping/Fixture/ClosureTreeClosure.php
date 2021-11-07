<?php

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

/**
 * @ORM\Entity
 */
class ClosureTreeClosure extends AbstractClosure
{
}
