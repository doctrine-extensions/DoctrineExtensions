<?php

namespace Gedmo\Tests\Tree\Fixture\Genealogy;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Woman extends Person
{
}
