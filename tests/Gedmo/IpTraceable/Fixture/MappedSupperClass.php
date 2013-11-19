<?php

namespace IpTraceable\Fixture;

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
    * @var string $createdAt
    *
    * @ORM\Column(name="created_at", type="string", length=45)
    * @Gedmo\IpTraceable(on="create")
    */
    protected $createdFromIp;

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
    * Get createdFromIp
    *
    * @return string $createdFromIp
    */
    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }
}