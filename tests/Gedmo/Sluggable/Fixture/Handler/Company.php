<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Handler;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Company
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
    private $title;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationClass", value="Gedmo\Tests\Sluggable\Fixture\Handler\User"),
     *          @Gedmo\SlugHandlerOption(name="mappedBy", value="company"),
     *          @Gedmo\SlugHandlerOption(name="inverseSlugField", value="slug")
     *      })
     * }, fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    private $alias;

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

    public function getAlias()
    {
        return $this->alias;
    }
}
