<?php
namespace Timestampable\Fixture;

use Gedmo\Timestampable\Timestampable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Author
{
    /**
     * @ORM\Column(name="author_name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(name="author_email", type="string", length=50, nullable=true)
     */
    private $email;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }


}
