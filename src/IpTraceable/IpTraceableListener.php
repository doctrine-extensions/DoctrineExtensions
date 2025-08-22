<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\IpTraceable\Mapping\Event\IpTraceableAdapter;
use Gedmo\Tool\IpAddressProviderInterface;

/**
 * The IpTraceable listener handles the update of
 * IPs on creation and update.
 *
 * @phpstan-extends AbstractTrackingListener<array, IpTraceableAdapter>
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class IpTraceableListener extends AbstractTrackingListener
{
    protected ?IpAddressProviderInterface $ipAddressProvider = null;

    /**
     * @var string|null
     */
    protected $ip;

    /**
     * Get the IP address value to set on an IP address field
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     * @param IpTraceableAdapter    $eventAdapter
     *
     * @return string|null
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        if ($this->ipAddressProvider instanceof IpAddressProviderInterface) {
            return $this->ipAddressProvider->getAddress();
        }

        return $this->ip;
    }

    /**
     * Set an IP address provider for the IP address value.
     */
    public function setIpAddressProvider(IpAddressProviderInterface $ipAddressProvider): void
    {
        $this->ipAddressProvider = $ipAddressProvider;
    }

    /**
     * Set an IP address value to return.
     *
     * If an IP address provider is also provided, it will take precedence over this value.
     *
     * @param string|null $ip
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function setIpValue($ip = null)
    {
        if (isset($ip) && false === filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("ip address is not valid $ip");
        }

        $this->ip = $ip;
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
