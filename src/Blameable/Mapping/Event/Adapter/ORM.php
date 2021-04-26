<?php

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;

/**
 * Doctrine event adapter for ORM adapted
 * for Blameable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements BlameableAdapter
{
}
