<?php

namespace Gedmo\Translatable\Document;

/**
* Gedmo\Translatable\Document\AbstractTranslation
*
* @MappedSuperclass
*/
abstract class AbstractTranslation
{
    /**
     * @var integer $id
     *
     * @Id
     */
    private $id;

    /**
     * @var string $locale
     *
     * @String
     */
    private $locale;

    /**
     * @var string $entity
     *
     * @String
     */
    private $entity;

    /**
     * @var string $field
     *
     * @String
     */
    private $field;

    /**
     * @var string $foreignKey
     *
     * @String(name="foreign_key")
     */
    private $foreignKey;

    /**
     * @var text $content
     *
     * @String
     */
    private $content;
    
    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set field
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * Get field
     *
     * @return string $field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set entity
     *
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return string $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }
    
    /**
     * Set foreignKey
     *
     * @param string $foreignKey
     */
    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }

    /**
     * Get foreignKey
     *
     * @return string $foreignKey
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }
    
    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text $content
     */
    public function getContent()
    {
        return $this->content;
    }
}