<?php

namespace Gedmo\Loggable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Gedmo\Loggable\Document\MappedSuperclass\AbstractLogEntry
 *
 * @MongoODM\MappedSuperclass
 */
abstract class AbstractLogEntry
{
    /**
     * @var integer $id
     *
     * @MongoODM\Id
     */
    protected $id;

    /**
     * @var string $action
     *
     * @MongoODM\Field(type="string")
     */
    protected $action;

    /**
     * @var \DateTime $loggedAt
     *
     * @MongoODM\Field(type="date")
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @MongoODM\Field(type="string")
     */
    protected $objectClass;

    /**
     * @var integer $version
     *
     * @MongoODM\Field(type="int")
     */
    protected $version;

    /**
     * @var string $data
     *
     * @MongoODM\Field(type="hash", nullable=true)
     */
    protected $data;

    /**
     * @var string $data
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    protected $username;

    /**
     * Get id
     *
     * @return integer
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
