<?php

namespace Gedmo\Timestampable\Mapping\Event;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the Timestampable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TimestampableAdapter extends AdapterInterface
{
    /**
     * Get the date value.
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return int|\DateTimeInterface
     */
    public function getDateValue($meta, $field);
}
