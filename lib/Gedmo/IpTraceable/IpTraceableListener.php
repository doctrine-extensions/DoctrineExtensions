<?php

namespace Gedmo\IpTraceable;

use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * The IpTraceable listener handles the update of
 * IPs on creation and update.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableListener extends AbstractTrackingListener
{
    /**
     * @var string|null
     */
    protected $ip;

    /**
     * Get the ipValue value to set on a ip field
     *
     * @param object $meta
     * @param string $field
     * @param AdapterInterface $eventAdapter
     *
     * @return null|string
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        return $this->ip;
    }

    /**
     * Set a ip value to return
     *
     * @param string $ip
     * @throws InvalidArgumentException
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
}
