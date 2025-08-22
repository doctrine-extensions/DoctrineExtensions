<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-extends AbstractTrackingListener<array, TimestampableAdapter>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class TimestampableListener extends AbstractTrackingListener
{
    /**
     * @param ClassMetadata<object> $meta
     * @param string                $field
     * @param TimestampableAdapter  $eventAdapter
     *
     * @return mixed
     */
    protected function getFieldValue($meta, $field, $eventAdapter)
    {
        return $eventAdapter->getDateValue($meta, $field);
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
