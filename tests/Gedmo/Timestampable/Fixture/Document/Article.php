<?php

namespace Timestampable\Fixture\Document;

/** 
 * @Document(collection="articles")
 */
class Article
{
    /** @Id */
    private $id;

    /**
     * @String
     */
    private $title;
    
    /** 
     * @ReferenceOne(targetDocument="Type")
     */
    private $type;
    
    /**
     * @var timestamp $created
     * 
     * @Timestamp
     * @gedmo:Timestampable(on="create")
     */
    private $created;
    
    /**
     * @var date $updated
     *
     * @Date
     * @gedmo:Timestampable
     */
    private $updated;
    
    /**
     * @var date $published
     *
     * @Date
     * @gedmo:Timestampable(on="change", field="type.title", value="Published")
     */
    private $published;

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
    
    public function getPublished()
    {
        return $this->published;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setType(Type $type)
    {
        $this->type = $type;
    }
    
    public function getType()
    {
        return $this->type;
    }
}
