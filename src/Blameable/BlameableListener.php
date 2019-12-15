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
    protected $actor;

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
            if (null !== $this->actor && ! is_object($this->actor)) {
                throw new InvalidArgumentException("Blame is reference, user must be an object");
            }

            return $this->actor;
        }

        // Ok so its not an association, then it is a string
        if (is_object($this->actor)) {
            if ($this->actor instanceof BlameableActorInterface) {
                return $this->actor->getActor();
            }

            if (method_exists($this->actor, 'getUsername')) {
                return (string) $this->actor->getUsername();
            }

            if (method_exists($this->actor, '__toString')) {
                return $this->actor->__toString();
            }

            throw new InvalidArgumentException("Field expects string, user must be a string, or object should have method getUsername or __toString");
        }

        return $this->actor;
    }

    /**
     * Set a user value to return
     *
     * @deprecated 2019/12/15 Replaced by setActor which is less opinionated
     * @see setActor
     *
     * @param mixed $user
     */
    public function setUserValue($actor)
    {
        $this->setActor($actor);
    }

    /**
     * Sets the actor used for Blameable
     *
     * @param mixed $actor
     */
    public function setActor($actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
