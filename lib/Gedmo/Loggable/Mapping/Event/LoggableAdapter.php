<?php

namespace Gedmo\Loggable\Mapping\Event;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Mapping\Event
 * @subpackage LoggableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface LoggableAdapter extends AdapterInterface
{
    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    function getDefaultLogEntryClass();

    /**
     * Get new version number
     *
     * @param ClassMetadata $meta
     * @param object $object
     * @return integer
     */
    function getNewVersion(ClassMetadata $meta, $object);
}