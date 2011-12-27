<?php

namespace Gedmo\Loggable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\String;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Date;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Int;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Hash;

/**
 * Gedmo\Loggable\Document\AbstractLogEntry
 *
 * @MappedSuperclass
 */
abstract class AbstractLogEntry
{
    /**
     * @var integer $id
     *
     * @Id
     */
    protected $id;

    /**
     * @var string $action
     *
     * @String
     */
    protected $action;

    /**
     * @var datetime $loggedAt
     *
     * @Index
     * @Date
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @String(nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @Index
     * @String
     */
    protected $objectClass;

    /**
     * @var integer $version
     *
     * @Int
     */
    protected $version;

    /**
     * @var text $data
     *
     * @Hash(nullable=true)
     */
    protected $data;

    /**
     * @var string $data
     *
     * @Index
     * @String(nullable=true)
     */
    protected $username;

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
     * @return datetime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set loggedAt
     *
     * @param string $loggedAt
     */
    public function setLoggedAt()
    {
        $this->loggedAt = new \DateTime();
    }

    /**
     * Get data
     *
     * @return array or null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Set current version
     *
     * @param integer $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get current version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }
}