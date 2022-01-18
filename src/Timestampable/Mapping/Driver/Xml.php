<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Timestampable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 */
class Xml extends BaseXml
{
    /**
     * List of types which are valid for timestamp
     *
     * @var array
     */
    private $validTypes = [
        'date',
        'date_immutable',
        'time',
        'time_immutable',
        'datetime',
        'datetime_immutable',
        'datetimetz',
        'datetimetz_immutable',
        'timestamp',
        'vardatetime',
        'integer',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $mapping = $this->_getMapping($meta->getName());

        if (isset($mapping->field)) {
            /**
             * @var \SimpleXmlElement
             */
            foreach ($mapping->field as $fieldMapping) {
                $fieldMappingDoctrine = $fieldMapping;
                $fieldMapping = $fieldMapping->children(self::GEDMO_NAMESPACE_URI);
                if (isset($fieldMapping->timestampable)) {
                    /**
                     * @var \SimpleXmlElement
                     */
                    $data = $fieldMapping->timestampable;

                    $field = $this->_getAttribute($fieldMappingDoctrine, 'name');
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'date', 'datetime' or 'time' in class - {$meta->getName()}");
                    }
                    if (!$this->_isAttributeSet($data, 'on') || !in_array($this->_getAttribute($data, 'on'), ['update', 'create', 'change'], true)) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->getName()}");
                    }

                    if ('change' === $this->_getAttribute($data, 'on')) {
                        if (!$this->_isAttributeSet($data, 'field')) {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->getName()}");
                        }
                        $trackedFieldAttribute = $this->_getAttribute($data, 'field');
                        $valueAttribute = $this->_isAttributeSet($data, 'value') ? $this->_getAttribute($data, 'value') : null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException('Timestampable extension does not support multiple value changeset detection yet.');
                        }
                        $field = [
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        ];
                    }
                    $config[$this->_getAttribute($data, 'on')][] = $field;
                }
            }
        }
    }

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes, true);
    }
}
