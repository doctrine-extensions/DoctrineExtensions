<?php

namespace SoftDeleteable\Fixture\Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", allowHardDelete=false)
 */
class UserAllowHardDelete
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $username;

    /** @ODM\Date */
    protected $deletedAt;

    /**
     * Sets deletedAt.
     *
     * @param Datetime $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Returns deletedAt.
     *
     * @return DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
