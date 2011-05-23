<?php

namespace Gedmo\Loggable\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * Gedmo\Loggable\Entity\AbstractLog
 *
 * @MappedSuperclass
 */
abstract class AbstractLogEntry
{
    /**
     * @var integer $id
     *
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string $action
     *
     * @Column(type="string", length=8)
     */
    private $action;

    /**
     * @var string $loggedAt
     *
     * @Column(name="logged_at", type="datetime")
     */
    private $loggedAt;

    /**
     * @var string $objectId
     *
     * @Column(name="object_id", length=32, nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @Column(name="object_class", type="string", length=255)
     */
    protected $objectClass;

    /**
     * @var integer $version
     *
     * @Column(type="integer")
     */
    private $version;

    /**
     * @var text $data
     *
     * @Column(type="array", nullable=true)
     */
    protected $data;

    /**
     * @var text $data
     *
     * @Column(length=255, nullable=true)
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
     * @return array
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