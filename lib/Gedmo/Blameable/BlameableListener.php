<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\Common\NotifyPropertyChanged,
    Gedmo\Timestampable\TimestampableListener,
    Gedmo\Blameable\Mapping\Event\BlameableAdapter;

/**
 * The Blameable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Blameable
 * @subpackage BlameableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableListener extends TimestampableListener
{
    /** @var BlameableAdapter */
    private $eventAdapter;

    /**
     * Allows to set a custom event adapter, e.g. a symfony one with the session.
     *
     * @param BlameableAdapter $eventAdapter
     */
    public function setAdapter($eventAdapter = null)
    {
        $this->eventAdapter = $eventAdapter;
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
     * @param $object
     * @param $ea
     * @param $meta
     * @param $field
     */
    protected function updateField($object, $ea, $meta, $field)
    {
        /** @var $ea BlameableAdapter */
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $ea->getUserValue($meta, $field);
        $property->setValue($object, $newValue);
        if ($object instanceof NotifyPropertyChanged) {
            $uow = $ea->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }

    /**
     * Return the manually set event adapter or let the parent do one
     *
     * @param \Doctrine\Common\EventArgs $args
     * @return \Gedmo\Mapping\Event\AdapterInterface|void
     */
    protected function getEventAdapter(EventArgs $args)
    {
        if ($this->eventAdapter) {
            $this->eventAdapter->setEventArgs($args);
            return $this->eventAdapter;
        }
        return parent::getEventAdapter($args);
    }
}
