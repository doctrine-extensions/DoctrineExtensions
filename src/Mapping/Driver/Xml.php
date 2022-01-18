<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use SimpleXMLElement;

/**
 * The mapping XmlDriver abstract class, defines the
 * metadata extraction function common among all
 * all drivers used on these extensions by file based
 * drivers.
 *
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class Xml extends File
{
    public const GEDMO_NAMESPACE_URI = 'http://gediminasm.org/schemas/orm/doctrine-extensions-mapping';
    public const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.xml';

    /**
     * Get attribute value.
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param string $attributeName
     *
     * @return string
     */
    protected function _getAttribute(SimpleXmlElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return (string) $attributes[$attributeName];
    }

    /**
     * Get boolean attribute value.
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param string $attributeName
     *
     * @return bool
     */
    protected function _getBooleanAttribute(SimpleXmlElement $node, $attributeName)
    {
        $rawValue = strtolower($this->_getAttribute($node, $attributeName));
        if ('1' === $rawValue || 'true' === $rawValue) {
            return true;
        }
        if ('0' === $rawValue || 'false' === $rawValue) {
            return false;
        }

        throw new InvalidMappingException(sprintf("Attribute %s must have a valid boolean value, '%s' found", $attributeName, $this->_getAttribute($node, $attributeName)));
    }

    /**
     * does attribute exist under a specific node
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param string $attributeName
     *
     * @return bool
     */
    protected function _isAttributeSet(SimpleXmlElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return isset($attributes[$attributeName]);
    }

    protected function _loadMappingFile($file)
    {
        $result = [];
        // We avoid calling `simplexml_load_file()` in order to prevent file operations in libXML.
        // If `libxml_disable_entity_loader(true)` is called before, `simplexml_load_file()` fails,
        // that's why we use `simplexml_load_string()` instead.
        // @see https://bugs.php.net/bug.php?id=62577.
        $xmlElement = simplexml_load_string(file_get_contents($file));
        $xmlElement = $xmlElement->children(self::DOCTRINE_NAMESPACE_URI);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = $this->_getAttribute($entityElement, 'name');
                $result[$entityName] = $entityElement;
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = $this->_getAttribute($mappedSuperClass, 'name');
                $result[$className] = $mappedSuperClass;
            }
        }

        return $result;
    }
}
