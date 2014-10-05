<?php
namespace IpTraceable\Fixture;

use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UsingTrait
{
    /**
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
