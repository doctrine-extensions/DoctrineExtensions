<?php

namespace Blameable\Fixture\Entity;

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
    * @var string $locale
    *
    * @Gedmo\Locale
    */
    protected $locale;

    /**
    * @var string $title
    *
    * @Gedmo\Translatable
    * @ORM\Column(name="name", type="string", length=255)
    */
    protected $name;

    /**
    * @var string $createdBy
    *
    * @ORM\Column(name="created_by", type="string")
    * @Gedmo\Blameable(on="create")
    */
    protected $createdBy;

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
    * Get createdBy
    *
    * @return string $createdBy
    */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
