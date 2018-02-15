<?php

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Blameable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements BlameableAdapter
{
}
