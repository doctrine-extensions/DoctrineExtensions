<?php
namespace Timestampable\Fixture;

/**
 * @Entity
 */
class Type
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=128)
     */
    private $title;
    
    /**
     * @OneToMany(targetEntity="Article", mappedBy="type")
     */
    private $articles;

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