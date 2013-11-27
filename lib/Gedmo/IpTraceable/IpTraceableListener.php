<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\Common\NotifyPropertyChanged,
    Gedmo\Exception\UnexpectedValueException,
    Gedmo\Exception\InvalidArgumentException;
use Gedmo\Timestampable\TimestampableListener;

/**
 * The IpTraceable listener handles the update of
 * IPs on creation and update.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableListener extends TimestampableListener
{
    protected $ip;

    /**
     * Get the ipValue value to set on a ip field
     *
     * @param object $meta
     * @param string $field
     * @return mixed
     */
    public function getIpValue($meta, $field)
    {
        return $this->ip;
    }

    /**
     * Set a ip value to return
     *
     * @param mixed $ip
     */
    public function setIpValue($ip = null)
    {
        if (isset($ip) && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException("ip address is not valid $ip");
        }

        $this->ip = $ip;
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
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $this->getIpValue($meta, $field);

        $property->setValue($object, $newValue);
        if ($object instanceof NotifyPropertyChanged) {
            $uow = $ea->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }
}
