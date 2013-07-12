<?php

namespace Timestampable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\MappedSuperclass
*/
class MappedSupperClass
{
    /**
    * @var integer $id
    *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
    * @var string $title
    *
    * @ORM\Column(name="name", type="string", length=255)
    */
    protected $name;

    /**
    * @var \DateTime $createdAt
    *
    * @ORM\Column(name="created_at", type="datetime")
    * @Gedmo\Timestampable(on="create")
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
