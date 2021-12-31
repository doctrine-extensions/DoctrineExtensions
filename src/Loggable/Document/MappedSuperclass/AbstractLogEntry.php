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
 * @MongoODM\MappedSuperclass
 */
#[MongoODM\MappedSuperclass]
abstract class AbstractLogEntry
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    protected $id;

    /**
     * @var string|null
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $action;

    /**
     * @var \DateTime|null
     *
     * @MongoODM\Field(type="date")
     */
    #[MongoODM\Field(type: Type::DATE)]
    protected $loggedAt;

    /**
     * @var string|null
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    #[MongoODM\Field(type: Type::STRING, nullable: true)]
    protected $objectId;

    /**
     * @var string|null
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $objectClass;

    /**
     * @var int|null
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
     * @var string|null
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    #[MongoODM\Field(type: Type::STRING, nullable: true)]
    protected $username;

    /**
     * Get id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get action
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get object class
     *
     * @return string|null
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     *
     * @return void
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * Get object id
     *
     * @return string|null
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set object id
     *
     * @param string $objectId
     *
     * @return void
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * Get username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get loggedAt
     *
     * @return \DateTime|null
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set loggedAt to "now"
     *
     * @return void
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
     *
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Set current version
     *
     * @param int $version
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get current version
     *
     * @return int|null
     */
    public function getVersion()
    {
        return $this->version;
    }
}
