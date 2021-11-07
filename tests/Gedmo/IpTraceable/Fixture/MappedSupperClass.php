<?php

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 */
class MappedSupperClass
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=191)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="string", length=45)
     * @Gedmo\IpTraceable(on="create")
     */
    protected $createdFromIp;

    /**
     * Get id
     *
     * @return int $id
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
