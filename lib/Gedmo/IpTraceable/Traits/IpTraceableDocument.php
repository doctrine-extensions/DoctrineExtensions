<?php

namespace Gedmo\IpTraceable\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * IpTraceable Trait, usable with PHP >= 5.4
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait IpTraceableDocument
{
    /**
     * @var string
     * @Gedmo\IpTraceable(on="create")
     * @ODM\String
     */
    protected $createdFromIp;

    /**
     * @var string
     * @Gedmo\IpTraceable(on="update")
     * @ODM\String
     */
    protected $updatedFromIp;

    /**
     * Sets createdFromIp.
     *
     * @param  string $createdFromIp
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
     * @param  string $updatedFromIp
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
