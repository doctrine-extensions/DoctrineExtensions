<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener extends AbstractTrackingListener
{
    /**
     * @param ClassMetadata $meta
     * @param string $field
     * @param TimestampableAdapter $eventAdapter
     * @return mixed
     */
    protected function getFieldValue($meta, $field, $eventAdapter)
    {
        return $eventAdapter->getDateValue($meta, $field);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
