<?php

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Exception\InvalidArgumentException;

/**
 * Doctrine event adapter for ORM adapted
 * for Blameable behavior. Simple version to manually inject username to use.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @package Gedmo\Blameable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements BlameableAdapter
{
    private $user;

    /**
     * {@inheritDoc}
     */
    public function getUserValue($meta, $field)
    {
        if ($meta->hasAssociation($field)) {
            if (null !== $this->user && ! is_object($this->user)) {
                throw new InvalidArgumentException("Blame is reference, user must be an object");
            }

            return $this->user;
        }

        // ok so its not an association, then it is a string
        if (is_object($this->user)) {
            if (! method_exists($this->user, 'getUsername')) {
                throw new InvalidArgumentException("Field expects string, user must be a string, or object should have method: getUsername");
            }

            return (string)$this->user->getUsername();
        }

        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }
}