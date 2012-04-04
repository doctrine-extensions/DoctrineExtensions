<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\Uploadable\Mapping\Validator;

/**
 * This is an annotation mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for SoftDeleteable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to define that this object is loggable
     */
    const UPLOADABLE = 'Gedmo\\Mapping\\Annotation\\Uploadable';
    const UPLOADABLE_FILE_MIME_TYPE = 'Gedmo\\Mapping\\Annotation\\UploadableFileMimeType';
    const UPLOADABLE_FILE_PATH = 'Gedmo\\Mapping\\Annotation\\UploadableFilePath';
    const UPLOADABLE_FILE_SIZE = 'Gedmo\\Mapping\\Annotation\\UploadableFileSize';
    const UPLOADABLE_FILE_INFO = 'Gedmo\\Mapping\\Annotation\\UploadableFileInfo';
    const UPLOADABLE_PATH = 'Gedmo\\Mapping\\Annotation\\UploadablePath';

    /**
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;
    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        // Nothing here for now
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        
        if (!$class) {
            // based on recent doctrine 2.3.0-DEV maybe will be fixed in some way
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($meta->name);
        }
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLE)) {
            $config['uploadable'] = true;
            $config['allowOverwrite'] = $annot->allowOverwrite;
            $config['appendNumber'] = $annot->appendNumber;
            $config['path'] = $annot->path;
            $config['pathMethod'] = '';
            $config['fileMimeTypeField'] = false;
            $config['filePathField'] = false;
            $config['fileSizeField'] = false;

            foreach ($class->getProperties() as $prop) {
                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_MIME_TYPE)) {
                    $config['fileMimeTypeField'] = $prop->getName();

                    Validator::validateFileMimeTypeField($meta, $config['fileMimeTypeField']);
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_PATH)) {
                    $config['filePathField'] = $prop->getName();

                    Validator::validateFilePathField($meta, $config['filePathField']);
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_SIZE)) {
                    $config['fileSizeField'] = $prop->getName();

                    Validator::validateFileSizeField($meta, $config['fileSizeField']);
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_INFO)) {
                    $config['fileInfoField'] = $prop->getName();
                }
            }

            if (!$config['filePathField']) {
                throw new InvalidMappingException(sprintf('Class "%s" must have an UploadableFilePath field.',
                    $class->getName()
                ));
            }

            if (!$config['fileInfoField']) {
                throw new InvalidMappingException(sprintf('Class "%s" must have an UploadableFileInfo field.',
                    $class->getName()
                ));
            }

            foreach ($class->getMethods() as $method) {
                if ($this->reader->getMethodAnnotation($method, self::UPLOADABLE_PATH)) {
                    $config['pathMethod'] = $method->getName();
                }
            }

            if ($config['path'] && $config['pathMethod'] === '') {
                $msg = 'You need to define the path in the %s annotation, or add a method with %s annotation.';

                throw new InvalidMappingException(sprintf($msg,
                    self::UPLOADABLE,
                    self::UPLOADABLE_PATH
                ));
            }
        } else {
            // We need to check if this class has a relation with Uploadable entities
            $associations = $meta->getAssociationMappings();

            foreach ($associations as $field => $association) {
                $refl = new \ReflectionClass($association['targetEntity']);

                if ($annot = $this->reader->getClassAnnotation($refl, self::UPLOADABLE)) {
                    $config['hasUploadables'] = true;

                    if (!isset($config['uploadables'])) {
                        $config['uploadables'] = array();
                    }

                    $config['uploadables'][] = array(
                        'class'         => $association['targetEntity'],
                        'property'      => $association['fieldName']
                    );
                }
            }
        }

        $this->validateFullMetadata($meta, $config);
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}