<?php

namespace Loggable\Fixture\Entity;

/**
 * @Entity
 * @gedmo:Loggable
 */
class Article
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @gedmo:Versioned
     * @Column(name="title", type="string", length=8)
     */
    private $title;

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
}
