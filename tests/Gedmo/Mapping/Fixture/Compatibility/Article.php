<?php
namespace Mapping\Fixture\Compatibility;

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
     * @var datetime $created
     *
     * @gedmo:Timestampable(on="create")
     * @Column(name="created", type="date")
     */
    private $created;

    /**
     * @var datetime $updated
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
     * @return datetime $created
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
     * @return datetime $updated
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