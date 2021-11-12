<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Compatibility;

/**
 * @Entity
 */
class Article
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @var \DateTime
     *
     * @gedmo:Timestampable(on="create")
     * @Column(name="created", type="date")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @Column(name="updated", type="datetime")
     * @gedmo:Timestampable
     */
    private $updated;

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

    /**
     * Get created
     *
     * @return \DateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * Get updated
     *
     * @return \DateTime $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }
}
