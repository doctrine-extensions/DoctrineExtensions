<?php

namespace Gedmo\Timestampable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements TimestampableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getDateValue($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        if (isset($mapping['type']) && $mapping['type'] === 'timestamp') {
            return time();
        }
        if (isset($mapping['type']) && $mapping['type'] == 'zenddate') {
            return new \Zend_Date();
        }
        if (isset($mapping['type']) && in_array($mapping['type'], array('date_immutable', 'time_immutable', 'datetime_immutable', 'datetimetz_immutable'), true)) {
            return new \DateTimeImmutable();
        }

        return \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))
            ->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
    }
}
