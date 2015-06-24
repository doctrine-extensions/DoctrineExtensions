<?php

namespace Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Embeddable
 */
class CompanyEmbedLink
{
    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $facebook;

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     * @return CompanyEmbedLink
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * @param string $facebook
     * @return CompanyEmbedLink
     */
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;

        return $this;
    }

}