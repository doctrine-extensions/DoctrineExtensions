<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation
 *
 * @MongoODM\MappedSuperclass
 */
#[MongoODM\MappedSuperclass]
abstract class AbstractTranslation
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
    protected $locale;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $objectClass;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $field;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string", name="foreign_key")
     */
    #[MongoODM\Field(name: 'foreign_key', type: Type::STRING)]
    protected $foreignKey;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    protected $content;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set field
     *
     * @param string $field
     *
     * @return static
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     *
     * @return static
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    /**
     * Get objectClass
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set foreignKey
     *
     * @param string $foreignKey
     *
     * @return static
     */
    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * Get foreignKey
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
