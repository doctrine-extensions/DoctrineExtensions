<?php

namespace Gedmo\Tool;

/**
 * Object wrapper interface
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface WrapperInterface
{
    /**
     * Get currently wrapped object
     * etc.: entity, document
     *
     * @return object
     */
    function getObject();

    /**
     * Extract property value from object
     *
     * @param string $property
     * @return mixed
     */
    function getPropertyValue($property);

    /**
     * Set the property
     *
     * @param string $property
     * @param mixed $value
     * @return \Gedmo\Tool\WrapperInterface
     */
    function setPropertyValue($property, $value);

    /**
     * Populates the object with given property values
     *
     * @param array $data
     * @return \Gedmo\Tool\WrapperInterface
     */
    function populate(array $data);

    /**
     * Checks if identifier is valid
     *
     * @return boolean
     */
    function hasValidIdentifier();

    /**
     * Get metadata
     *
     * @return object
     */
    function getMetadata();

    /**
     * Get the object identifier, $single or composite
     *
     * @param boolean $single
     * @return array|mixed
     */
    function getIdentifier($single = true);

    /**
     * Get root object class name
     *
     * @return string
     */
    function getRootObjectName();
}