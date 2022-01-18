<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;

/**
 * Doctrine event adapter for ODM adapted
 * for Blameable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
final class ODM extends BaseAdapterODM implements BlameableAdapter
{
}
