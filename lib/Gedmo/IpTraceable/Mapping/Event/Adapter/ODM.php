<?php

namespace Gedmo\IpTraceable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\IpTraceable\Mapping\Event\IpTraceableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements IpTraceableAdapter
{
}