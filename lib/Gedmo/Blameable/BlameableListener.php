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
    protected $value;

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
            if (null !== $this->value && ! is_object($this->value)) {
                throw new InvalidArgumentException("Blame is reference, user must be an object or callable");
            }

            if (\is_callable($this->value)) {
                return ($this->value)();
            }

            return $this->value;
        }

        // ok so its not an association, then it is a string
        if (is_object($this->value)) {
            if (method_exists($this->value, 'getUsername')) {
                return (string) $this->value->getUsername();
            }
            if (method_exists($this->value, '__toString')) {
                return $this->value->__toString();
            }
            throw new InvalidArgumentException("Field expects string, user must be a string, or object should have method getUsername or __toString");
        }

        return $this->value;
    }

    /**
     * Set a user value to return
     *
     * @param mixed $value
     */
    public function setUserValue($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
