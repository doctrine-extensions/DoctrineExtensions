<?php

namespace Gedmo\SoftDeleteable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\SoftDeleteable\Mapping\Event\SoftDeleteableAdapter;
use Gedmo\Exception\InvalidArgumentException;

/**
 * Doctrine event adapter for ORM adapted
 * for SoftDeleteable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements SoftDeleteableAdapter
{
}