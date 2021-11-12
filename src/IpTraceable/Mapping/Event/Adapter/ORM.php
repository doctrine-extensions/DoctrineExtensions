<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable\Mapping\Event\Adapter;

use Gedmo\IpTraceable\Mapping\Event\IpTraceableAdapter;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;

/**
 * Doctrine event adapter for ORM adapted
 * for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class ORM extends BaseAdapterORM implements IpTraceableAdapter
{
}
