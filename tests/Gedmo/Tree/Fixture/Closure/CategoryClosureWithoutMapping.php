<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Closure;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class CategoryClosureWithoutMapping extends AbstractClosure
{
}
