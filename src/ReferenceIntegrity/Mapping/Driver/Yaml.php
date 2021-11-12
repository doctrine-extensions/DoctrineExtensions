<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * This is a yaml mapping driver for ReferenceIntegrity
 * extension. Used for extraction of extended
 * metadata from yaml specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->getName());
        $validator = new Validator();

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $property => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['referenceIntegrity'])) {
                    if (!$meta->hasField($property)) {
                        throw new InvalidMappingException(sprintf('Unable to find reference integrity [%s] as mapped property in entity - %s', $property, $meta->getName()));
                    }

                    if (empty($mapping['fields'][$property]['mappedBy'])) {
                        throw new InvalidMappingException(sprintf("'mappedBy' should be set on '%s' in '%s'", $property, $meta->getName()));
                    }

                    if (!in_array($fieldMapping['gedmo']['referenceIntegrity'], $validator->getIntegrityActions(), true)) {
                        throw new InvalidMappingException(sprintf('Field - [%s] does not have a valid integrity option, [%s] in class - %s', $property, implode(', ', $validator->getIntegrityActions()), $meta->getName()));
                    }

                    $config['referenceIntegrity'][$property][$mapping['fields'][$property]['mappedBy']] =
                        $fieldMapping['gedmo']['referenceIntegrity'];
                }
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }
}
