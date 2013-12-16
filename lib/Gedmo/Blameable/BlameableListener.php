<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\NotifyPropertyChanged;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;

/**
 * The Blameable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableListener extends TimestampableListener
{
    protected $users = array();

    /**
     * Get the user value to set on a blameable field
     *
     * @param object $meta
     * @param string $field
     * @return mixed
     */
    public function getUserValue($meta, $field, $whom)
    {
        $whom = $whom ?: '__default__';
        $user = $this->users[$whom];

        if ($meta->hasAssociation($field)) {
            if (null !== $user && ! is_object($user)) {
                throw new InvalidArgumentException("Blame is reference, user must be an object");
            }

            return $user;
        }

        // ok so its not an association, then it is a string
        if (is_object($user)) {
            if (method_exists($user, 'getUsername')) {
                return (string)$user->getUsername();
            }
            if (method_exists($user, '__toString')) {
                return $user->__toString();
            }
            throw new InvalidArgumentException("Field expects string, user must be a string, or object should have method getUsername or __toString");
        }

        return $user;
    }

    /**
     * Set a user value to return
     *
     * @param mixed $user
     */
    public function setUserValue($user)
    {
        $this->setUserValueFor('__default__', $user);
    }

    public function setUserValueFor($whom, $user)
    {
        $this->users[$whom] = $user;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Updates a field
     *
     * @param mixed $object
     * @param BlameableAdapter $ea
     * @param $meta
     * @param $field
     */
    protected function updateField($object, $ea, $meta, $field, array $options = array())
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $this->getUserValue($meta, $field, $options['whom']);

        $property->setValue($object, $newValue);
        if ($object instanceof NotifyPropertyChanged) {
            $uow = $ea->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }
}
