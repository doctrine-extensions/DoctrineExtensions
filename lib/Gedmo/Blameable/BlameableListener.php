<?php

namespace Gedmo\Blameable;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;

/**
 * The Blameable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableListener extends AbstractTrackingListener
{
    protected $user;

    /**
     * Get the user value to set on a blameable field
     *
     * @param object $meta
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        if ($meta->hasAssociation($field)) {
            if (null !== $this->user && ! is_object($this->user)) {
                throw new InvalidArgumentException("Blame is reference, user must be an object");
            }

            return $this->user;
        }

        // ok so its not an association, then it is a string
        if (is_object($this->user)) {
            if (method_exists($this->user, 'getUsername')) {
                return (string) $this->user->getUsername();
            }
            if (method_exists($this->user, '__toString')) {
                return $this->user->__toString();
            }
            throw new InvalidArgumentException("Field expects string, user must be a string, or object should have method getUsername or __toString");
        }

        return $this->user;
    }

    /**
     * Set a user value to return
     *
     * @param mixed $user
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
