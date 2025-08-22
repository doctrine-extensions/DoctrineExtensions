<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Helper method to retrieve the entity manager for ORM hydrator classes.
 *
 * This trait includes a compatibility layer for the renamed `Doctrine\ORM\Internal\Hydration\AbstractHydrator::$_em`
 * property between ORM 2.x and 3.x.
 *
 * @mixin AbstractHydrator
 *
 * @internal
 */
trait EntityManagerRetriever
{
    protected function getEntityManager(): EntityManagerInterface
    {
        return property_exists($this, '_em') ? $this->_em : $this->em;
    }
}
