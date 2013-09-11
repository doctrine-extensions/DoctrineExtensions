<?php

namespace Gedmo\Sluggable\Entity\MappedSuperclass;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gedmo\Sluggable\Entity\AbstractSlugEntry
 *
 * @ORM\MappedSuperclass
 *
 * @author Martin Jantosovic <jantosovic.martin@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractSlugEntry {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Slug field name
     *
     * @ORM\Column(length=255, name="slug_field")
     */
    protected $slugField;

    /**
     * Slug field value
     *
     * @ORM\Column(length=255, name="slug_value")
     */
    protected $slugValue;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", length=32, nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     */
    protected $objectClass;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * Get object class
     *
     * @return string
     */
    public function getObjectClass() {
        return $this->objectClass;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     */
    public function setObjectClass($objectClass) {
        $this->objectClass = $objectClass;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId() {
        return $this->objectId;
    }

    /**
     * Set object id
     *
     * @param string $objectId
     */
    public function setObjectId($objectId) {
        $this->objectId = $objectId;
    }

    /**
     * Get created
     *
     * @return datetime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set created
     */
    public function setCreated() {
        $this->created = new \DateTime();
    }

    /**
     * Get slug field
     *
     * @return string
     */
    public function getSlugField() {
        return $this->slugField;
    }

    /**
     * Set slug field
     *
     * @param string $slug
     */
    public function setSlugField($slugField) {
        $this->slugField = $slugField;
    }

    /**
     * Get slug value
     *
     * @return string
     */
    public function getSlugValue() {
        return $this->slugValue;
    }

    /**
     * Set slug value
     *
     * @param string $slug
     */
    public function setSlugValue($slugValue) {
        $this->slugValue = $slugValue;
    }

}
