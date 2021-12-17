<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Gedmo\Loggable\Document\MappedSuperclass\AbstractLogEntry
 *
 * @MongoODM\MappedSuperclass
 */
#[MongoODM\MappedSuperclass]
abstract class AbstractLogEntry
{
    /**
     * @var int
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    protected $id;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $action;

    /**
     * @var \DateTime
     *
     * @MongoODM\Field(type="date")
     */
    #[MongoODM\Field(type: Type::DATE)]
    protected $loggedAt;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    #[MongoODM\Field(type: Type::STRING, nullable: true)]
    protected $objectId;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $objectClass;

    /**
     * @var int
     *
     * @MongoODM\Field(type="int")
     */
    #[MongoODM\Field(type: Type::INT)]
    protected $version;

    /**
     * @var array<string, mixed>|null
     *
     * @MongoODM\Field(type="hash", nullable=true)
     */
    #[MongoODM\Field(type: Type::HASH, nullable: true)]
    protected $data;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    #[MongoODM\Field(type: Type::STRING, nullable: true)]
    protected $username;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get object class
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set object id
     *
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get loggedAt
     *
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set loggedAt to "now"
     */
    public function setLoggedAt()
    {
        $this->loggedAt = new \DateTime();
    }

    /**
     * Get data
     *
     * @return array<string, mixed>|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array<string, mixed> $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Set current version
     *
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get current version
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
