<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;
use Gedmo\SoftDeleteable\Mapping\Validator;

/**
 * This is a yaml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Timestampable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
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
            if (isset($classMapping['soft_deleteable'])) {
                $config['softDeleteable'] = true;

                if (!isset($classMapping['soft_deleteable']['field_name'])) {
                    throw new InvalidMappingException('Field name for SoftDeleteable class is mandatory.');
                }

                $fieldName = $classMapping['soft_deleteable']['field_name'];

                Validator::validateField($meta, $fieldName);

                $config['fieldName'] = $fieldName;

                $config['timeAware'] = false;
                if (isset($classMapping['soft_deleteable']['time_aware'])) {
                    if (!is_bool($classMapping['soft_deleteable']['time_aware'])) {
                        throw new InvalidMappingException('timeAware must be boolean. '.gettype($classMapping['soft_deleteable']['time_aware']).' provided.');
                    }
                    $config['timeAware'] = $classMapping['soft_deleteable']['time_aware'];
                }

                $config['hardDelete'] = true;
                if (isset($classMapping['soft_deleteable']['hard_delete'])) {
                    if (!is_bool($classMapping['soft_deleteable']['hard_delete'])) {
                        throw new InvalidMappingException('hardDelete must be boolean. '.gettype($classMapping['soft_deleteable']['hard_delete']).' provided.');
                    }
                    $config['hardDelete'] = $classMapping['soft_deleteable']['hard_delete'];
                }
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }
}
