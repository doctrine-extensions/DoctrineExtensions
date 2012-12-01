<?php

namespace Sluggable\Fixture\Handler;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $username;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationField", value="company"),
     *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="alias"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, separator="-", updatable=true, fields={"username"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

    public function setCompany(Company $company = null)
    {
        $this->company = $company;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
