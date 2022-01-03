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
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * The IpTraceable listener handles the update of
 * IPs on creation and update.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
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
     * @param ClassMetadata    $meta
     * @param string           $field
     * @param AdapterInterface $eventAdapter
     *
     * @return string|null
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        return $this->ip;
    }

    /**
     * Set a ip value to return
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
