<?php

namespace Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\EmbeddedDocument
 * @Gedmo\Loggable
 */
class User
{
    /**
     * @Gedmo\Versioned
     * @ODM\String
     */
    private $firstName;

    /**
     * @Gedmo\Versioned
     * @ODM\String
     */
    private $lastName;

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }
}
