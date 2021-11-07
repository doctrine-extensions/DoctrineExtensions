<?php

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;

/**
 * @ORM\Entity
 */
class UsingTrait
{
    /*
     * Hook ipTraceable behavior
     * updates createdFromIp, updatedFromIp fields
     */
    use IpTraceableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
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
