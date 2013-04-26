<?php

namespace Gedmo\Timestampable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TimestampableAdapter extends AdapterInterface
{
    /**
     * Get the date value
     *
     * @param object $meta
     * @param string $field
     * @return mixed
     */
    function getDateValue($meta, $field);
}