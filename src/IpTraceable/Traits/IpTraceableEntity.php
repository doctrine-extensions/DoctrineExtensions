<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait for IP traceable objects.
 *
 * This implementation provides a mapping configuration for the Doctrine ORM.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
trait IpTraceableEntity
{
    /**
     * @var string
     *
     * @Gedmo\IpTraceable(on="create")
     *
     * @ORM\Column(length=45, nullable=true)
     */
    #[ORM\Column(length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'create')]
    protected $createdFromIp;

    /**
     * @var string
     *
     * @Gedmo\IpTraceable(on="update")
     *
     * @ORM\Column(length=45, nullable=true)
     */
    #[ORM\Column(length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'update')]
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
