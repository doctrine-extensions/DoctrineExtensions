<?php

namespace Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\EmbeddedDocument
 * @Gedmo\Loggable
 */
class Author
{
    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    private $email;

    public function __toString()
    {
        return $this->getName();
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
