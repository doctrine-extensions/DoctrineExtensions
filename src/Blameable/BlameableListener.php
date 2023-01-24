<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;

/**
 * The Blameable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class BlameableListener extends AbstractTrackingListener
{
    /**
     * @var mixed
     */
    protected $user;

    /**
     * Get the user value to set on a blameable field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return mixed
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        if ($meta->hasAssociation($field)) {
            if (null !== $this->user && !is_object($this->user)) {
                throw new InvalidArgumentException('Blame is reference, user must be an object');
            }

            return $this->user;
        }

        // ok so it's not an association, then it is a string, or an object
        if (is_object($this->user)) {
            if (method_exists($this->user, 'getUserIdentifier')) {
                return (string) $this->user->getUserIdentifier();
            }
            if (method_exists($this->user, 'getUsername')) {
                return (string) $this->user->getUsername();
            }
            if (method_exists($this->user, '__toString')) {
                return $this->user->__toString();
            }

            throw new InvalidArgumentException('Field expects string, user must be a string, or object should have method getUserIdentifier, getUsername or __toString');
        }

        return $this->user;
    }

    /**
     * Set a user value to return
     *
     * @param mixed $user
     *
     * @return void
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
