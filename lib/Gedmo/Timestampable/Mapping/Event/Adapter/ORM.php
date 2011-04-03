<?php

namespace Gedmo\Timestampable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Doctrine event adapter for ORM adapted
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Timestampable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM
{
    /**
     * Get the date value
     *
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return mixed
     */
    public function getDateValue(ClassMetadataInfo $meta, $field)
    {
        return new \DateTime();
    }
}