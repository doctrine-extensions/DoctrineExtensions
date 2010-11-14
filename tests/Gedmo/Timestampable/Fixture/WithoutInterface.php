<?php
namespace Timestampable\Fixture;

/**
 * @Entity
 */
class WithoutInterface
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(type="string", length=128)
     */
    private $title;
    
    /**
     * @gedmo:Timestampable(on="create")
     * @Column(type="date")
     */
    private $created;
    
    /**
     * @Column(type="datetime")
     * @gedmo:Timestampable(on="update")
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
    
    public function getCreated()
    {
        return $this->created;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
}