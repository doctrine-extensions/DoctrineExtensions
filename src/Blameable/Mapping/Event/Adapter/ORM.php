<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;

/**
 * Doctrine event adapter for ORM adapted
 * for Blameable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
final class ORM extends BaseAdapterORM implements BlameableAdapter
{
}
