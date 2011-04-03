<?php

namespace Gedmo\Timestampable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Doctrine event adapter for ODM adapted
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Timestampable\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM
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
        $mapping = $meta->getFieldMapping($field);
        if (isset($mapping['type']) && $mapping['type'] === 'timestamp') {
            return time();
        }
        return new \DateTime();
    }
}