<?php

namespace Gedmo\Uploadable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for uploadable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class UploadableMetadata implements ExtensionMetadataInterface
{
    const GENERATOR_SHA1 = 'SHA1';
    const GENERATOR_ALPHANUMERIC = 'ALPHANUMERIC';
    const GENERATOR_NONE = 'NONE';
    const GENERATOR_IFACE = 'Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface';

    /**
     * List of uploadable options
     *
     * @var array
     */
    private $options = array();

    /**
     * Map uploadable options
     *
     * @param array $options
     */
    public function map(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get uploadable options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get an option by $name
     *
     * @param string $name
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return;
        }
        if (!isset($this->options['filePathField']) || !$this->options['filePathField']) {
            throw new InvalidMappingException(sprintf('Class "%s" must have an UploadableFilePath field.',
                $meta->name
            ));
        }
        $refl = $meta->getReflectionClass();

        if ($this->options['pathMethod'] !== '' && !$refl->hasMethod($this->options['pathMethod'])) {
            throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have method "%s"!',
                $meta->name,
                $this->options['pathMethod']
            ));
        }

        if ($this->options['callback'] !== '' && !$refl->hasMethod($this->options['callback'])) {
            throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have method "%s"!',
                $meta->name,
                $this->options['callback']
            ));
        }

        $this->options['maxSize'] = (double)$this->options['maxSize'];

        if ($this->options['maxSize'] < 0) {
            throw new InvalidMappingException(sprintf('Option "maxSize" must be a number 0 or higher for class "%s".',
                $meta->name
            ));
        }

        if (!is_array($this->options['allowedTypes'])) {
            $this->options['allowedTypes'] = array_filter(explode(',', $this->options['allowedTypes']), function($type) {
                return !empty($type);
            });
        }
        if (!is_array($this->options['disallowedTypes'])) {
            $this->options['disallowedTypes'] = array_filter(explode(',', $this->options['disallowedTypes']), function($type) {
                return !empty($type);
            });
        }
        if ($this->options['allowedTypes'] && $this->options['disallowedTypes']) {
            $msg = 'You\'ve set "allowedTypes" and "disallowedTypes" options. You must set only one in class "%s".';
            throw new InvalidMappingException(sprintf($msg,
                $meta->name
            ));
        }

        switch ($gen = $this->options['filenameGenerator']) {
            case self::GENERATOR_ALPHANUMERIC:
            case self::GENERATOR_SHA1:
            case self::GENERATOR_NONE:
                break;
            default:
                if (!class_exists($gen) || !($refl = new \ReflectionClass($gen)) || !$refl->implementsInterface(self::GENERATOR_IFACE)) {
                    $msg = 'Class "%s" needs a valid value for filenameGenerator. It can be: SHA1, ALPHANUMERIC, NONE or ';
                    $msg .= 'a class implementing FilenameGeneratorInterface.';

                    throw new InvalidMappingException(sprintf($msg,
                        $meta->name
                    ));
                }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->options) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->options = $data;
    }
}
