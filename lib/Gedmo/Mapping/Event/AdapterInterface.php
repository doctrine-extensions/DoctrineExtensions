<?php

namespace Gedmo\Mapping\Event;

use Doctrine\Common\EventArgs;

/**
 * Doctrine event adapter interface is used
 * to retrieve common functionality for Doctrine
 * events
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Event
 * @subpackage AdapterInterface
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface AdapterInterface
{
    /**
     * Set the eventargs
     *
     * @param EventArgs $args
     */
    function setEventArgs(EventArgs $args);

    /**
     * Get the name of domain object
     *
     * @return string
     */
    function getDomainObjectName();

    /**
     * Get the name of used manager for this
     * event adapter
     */
    function getManagerName();
}