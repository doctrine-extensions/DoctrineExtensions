<?php

namespace Gedmo\Blameable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Blameable behavior
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @package Gedmo\Blameable\Mapping\Event
 * @subpackage BlameableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface BlameableAdapter extends AdapterInterface
{
    /**
     * Get the user value
     *
     * @param object $meta
     * @param string $field
     * @return mixed
     */
    public function getUserValue($meta, $field);

    /**
     * Set a user value to return
     *
     * @return mixed
     */
    public function setUserValue($user);
}