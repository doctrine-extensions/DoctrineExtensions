<?php

namespace Mapping\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Sluggable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Sluggable
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\Sluggable
     * @ORM\Column(name="code", type="string", length=16)
     */
    private $code;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Some\Class", options={
     *          @Gedmo\SlugHandlerOption(name="relation", value="parent"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/")
     *      }),
     *      @Gedmo\SlugHandler(class="Some\Class2", options={
     *          @Gedmo\SlugHandlerOption(name="option", value="val"),
     *          @Gedmo\SlugHandlerOption(name="option2", value="val2")
     *      })
     * }, separator="-", updatable=false)
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    private $slug;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
