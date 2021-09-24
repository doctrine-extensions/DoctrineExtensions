<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class CompanyEmbedLink
{
    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=191, nullable=true)
     * @Gedmo\Translatable
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'website', type: Types::STRING, length: 191, nullable: true)]
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="string", length=191, nullable=true)
     * @Gedmo\Translatable
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'facebook', type: Types::STRING, length: 191, nullable: true)]
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
     *
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
     *
     * @return CompanyEmbedLink
     */
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;

        return $this;
    }
}
