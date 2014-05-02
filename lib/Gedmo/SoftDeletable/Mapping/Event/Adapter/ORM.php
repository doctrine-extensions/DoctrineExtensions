<?php

namespace Gedmo\SoftDeletable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\SoftDeletable\Mapping\Event\SoftDeletableAdapter;
use Gedmo\Exception\InvalidArgumentException;

/**
 * Doctrine event adapter for ORM adapted
 * for SoftDeletable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements SoftDeletableAdapter
{
}