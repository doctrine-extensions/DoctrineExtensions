<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['translation']['entity'])) {
                $translationEntity = $classMapping['translation']['entity'];
                if (!$cl = $this->getRelatedClassName($meta, $translationEntity)) {
                    throw new InvalidMappingException("Translation entity class: {$translationEntity} does not exist.");
                }
                $config['translationClass'] = $cl;
            }
            if (isset($classMapping['translation']['locale'])) {
                $config['locale'] = $classMapping['translation']['locale'];
            } elseif (isset($classMapping['translation']['language'])) {
                $config['locale'] = $classMapping['translation']['language'];
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                $config = $this->buildFieldConfiguration($field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $overrideMapping) {
                $config = $this->buildFieldConfiguration($field, $overrideMapping, $config);
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->getName()}");
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    /**
     * @param array<string, mixed> $fieldMapping
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function buildFieldConfiguration(string $field, array $fieldMapping, array $config): array
    {
        if (isset($fieldMapping['gedmo'])) {
            if (in_array('translatable', $fieldMapping['gedmo'], true) || isset($fieldMapping['gedmo']['translatable'])) {
                // fields cannot be overrided and throws mapping exception
                $config['fields'][] = $field;
                if (isset($fieldMapping['gedmo']['translatable']['fallback'])) {
                    $config['fallback'][$field] = $fieldMapping['gedmo']['translatable']['fallback'];
                }
            }
        }

        return $config;
    }
}
