<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable\Traits;

/**
 * IpTraceable Trait, usable with PHP >= 5.4
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
trait IpTraceable
{
    /**
     * @var string
     */
    protected $createdFromIp;

    /**
     * @var string
     */
    protected $updatedFromIp;

    /**
     * Sets createdFromIp.
     *
     * @param string $createdFromIp
     *
     * @return $this
     */
    public function setCreatedFromIp($createdFromIp)
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    /**
     * Returns createdFromIp.
     *
     * @return string
     */
    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }

    /**
     * Sets updatedFromIp.
     *
     * @param string $updatedFromIp
     *
     * @return $this
     */
    public function setUpdatedFromIp($updatedFromIp)
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    /**
     * Returns updatedFromIp.
     *
     * @return string
     */
    public function getUpdatedFromIp()
    {
        return $this->updatedFromIp;
    }
}
