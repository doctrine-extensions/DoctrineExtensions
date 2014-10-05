<?php

namespace Tree\Fixture\Closure;

use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CategoryWithoutLevelClosure extends AbstractClosure
{
}
