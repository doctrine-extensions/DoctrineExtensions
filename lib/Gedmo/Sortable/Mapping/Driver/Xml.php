<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * List of types which are valid for position field
     *
     * @var array
     */
    private $validTypes = array(
        'int',
        'integer',
        'smallint',
        'bigint'
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);

        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->{'sortable-position'})) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    if ($meta->isNullable($field)) {
                        throw new InvalidMappingException("Sortable field: '$field' - cannot be nullable in class - {$meta->name}");
                    }
                    $config['position'] = $field;
                }
            }
            $this->readSortableGroups($xml->field, $config, 'name');
        }


        // Search for sortable-groups in association mappings
        if (isset($xml->{'many-to-one'})) {
            $this->readSortableGroups($xml->{'many-to-one'}, $config);
        }

        // Search for sortable-groups in association mappings
        if (isset($xml->{'many-to-many'})) {
            $this->readSortableGroups($xml->{'many-to-many'}, $config);
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (!isset($config['position'])) {
                throw new InvalidMappingException("Missing property: 'position' in class - {$meta->name}");
            }
        }
    }

    private function readSortableGroups($mapping, array &$config, $fieldAttr='field')
    {
        foreach ($mapping as $map) {
            $mappingDoctrine = $map;
            /**
             * @var \SimpleXmlElement $mapping
             */
            $map = $map->children(self::GEDMO_NAMESPACE_URI);

            $field = $this->_getAttribute($mappingDoctrine, $fieldAttr);
            if (isset($map->{'sortable-group'})) {
                if (!isset($config['groups'])) {
                    $config['groups'] = array();
                }
                $config['groups'][] = $field;
            }
        }
    }

    /**
     * Checks if $field type is valid as Sortable Position field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
