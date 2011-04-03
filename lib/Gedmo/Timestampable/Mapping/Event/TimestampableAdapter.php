<?php

namespace Gedmo\Timestampable\Mapping\Event;

/**
 * Doctrine event adapter interface
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Timestampable\Mapping\Event
 * @subpackage TimestampableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TimestampableAdapter
{
    /**
     * Get the date value
     *
     * @param ClassMetadata $meta
     * @param string $field
     * @return mixed
     */
    function getDateValue($meta, $field);
}