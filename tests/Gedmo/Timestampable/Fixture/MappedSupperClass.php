<?php

namespace Timestampable\Fixture;

/**
* @MappedSuperclass
*/
class MappedSupperClass
{
    /**
    * @var integer $id
    *
    * @Column(name="id", type="integer")
    * @Id
    * @GeneratedValue(strategy="AUTO")
    */
    protected $id;
    
    /**
    * @var string $locale
    *
    * @gedmo:Locale
    */
    protected $locale;
    
    /**
    * @var string $title
    *
    * @gedmo:Translatable
    * @Column(name="name", type="string", length=255)
    */
    protected $name;
    
    /**
    * @var \DateTime $createdAt
    *
    * @Column(name="created_at", type="datetime")
    * @gedmo:Timestampable(on="create")
    */
    protected $createdAt;
    
    /**
    * Get id
    *
    * @return integer $id
    * @codeCoverageIgnore
    */
    public function getId()
    {
        return $this->id;
    }
    
    /**
    * Set name
    *
    * @param string $name
    */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
    * Get name
    *
    * @return string $name
    */
    public function getName()
    {
        return $this->name;
    }
    
    /**
    * Get createdAt
    *
    * @return \DateTime $createdAt
    */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}