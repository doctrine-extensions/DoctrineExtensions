<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Sortable
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
        'bigint',
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
            foreach ($xml->field as $mappingDoctrine) {
                $mapping = $mappingDoctrine->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if ($mapping->count() > 0 && isset($mapping->sortable)) {
                    $sortable = $mapping->sortable;
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }

                    $groups = array();
                    if ($this->_isAttributeSet($sortable, 'groups')) {
                        $groups = array_map('trim', explode(',', (string) $this->_getAttribute($sortable, 'groups')));
                        foreach ($groups as $group) {
                            if (!$meta->hasField($group) && !$meta->isSingleValuedAssociation($group)) {
                                throw new InvalidMappingException(
                                    "Sortable field: '{$field}' group: {$group} - is not a mapped
                                    or single valued association property in class {$meta->name}"
                                );
                            }
                        }
                    }

                    $config['sortables'][$field] = [
                        'position' => $field,
                        'groups' => $groups,
                        'useObjectClass' => $meta->name
                    ];
                }
            }
        }
    }

    /**
     * Checks if $field type is valid as Sortable Position field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
