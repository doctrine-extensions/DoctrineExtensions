<?php

namespace Gedmo\Uploadable;

use Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\UnitOfWork,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\Common\EventArgs,
    Gedmo\Exception\UploadableDirectoryNotFoundException,
    Gedmo\Exception\UploadablePartialException,
    Gedmo\Exception\UploadableCantWriteException,
    Gedmo\Exception\UploadableExtensionException,
    Gedmo\Exception\UploadableFormSizeException,
    Gedmo\Exception\UploadableIniSizeException,
    Gedmo\Exception\UploadableNoFileException,
    Gedmo\Exception\UploadableNoTmpDirException,
    Gedmo\Exception\UploadableUploadException;

/**
 * Uploadable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable
 * @subpackage UploadableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class UploadableListener extends MappedEventSubscriber
{
    const ACTION_INSERT = 'INSERT';
    const ACTION_UPDATE = 'UPDATE';
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'onFlush'
        );
    }

    /**
     * Handle file-uploading depending on the action
     * being done with objects
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));

            if ($config = $this->getConfiguration($om, $meta->name)) {
                if (isset($config['uploadable']) && $config['uploadable']) {
                    $this->processFile($om, $uow, $ea, $meta, $config, $object, self::ACTION_INSERT);
                }
            }
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));

            if ($config = $this->getConfiguration($om, $meta->name)) {
                if (isset($config['uploadable']) && $config['uploadable']) {
                    $this->removeFile($meta, $config, $object);
                    $this->processFile($om, $uow, $ea, $meta, $config, $object, self::ACTION_UPDATE);
                }
            }
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            
            if ($config = $this->getConfiguration($om, $meta->name)) {
                if (isset($config['uploadable']) && $config['uploadable']) {
                    $this->removeFile($meta, $config, $object);
                }
            }
        }
    }

    /**
     * If it's a Uploadable object, verify if the file was uploaded.
     * If that's the case, process it.
     *
     * @param EventArgs $args
     * @return void
     */
    public function processFile(ObjectManager $om, UnitOfWork $uow, $ea, ClassMetadata $meta, array $config, $object, $action)
    {
        $refl = $meta->getReflectionClass();
        $pathMethod = $refl->getMethod($config['pathMethod']);
        $pathMethod->setAccessible(true);
        $path = $pathMethod->invoke($object);
        $path = substr($path, strlen($path) - 1) === '/' ? substr($path, 0, strlen($path) - 2) : $path;
        $indexMethod = $refl->getMethod($config['filesArrayIndexMethod']);
        $indexMethod->setAccessible(true);
        $index = $indexMethod->invoke($object);
        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);
        $identifierProp = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName());
        $identifierProp->setAccessible(true);
        $identifier = $identifierProp->getValue($object);

        if ($config['fileMimeTypeField']) {
            $fileMimeTypeField = $refl->getProperty($config['fileMimeTypeField']);
            $fileMimeTypeField->setAccessible(true);
        }

        if ($config['fileSizeField']) {
            $fileSizeField = $refl->getProperty($config['fileSizeField']);
            $fileSizeField->setAccessible(true);
        }

        $file = $this->getFile($index);

        if ($file && is_array($file)) {
            // If it's a single file we create an array anyway, so we can process
            // a collection or a single file in the same way
            if (isset($file['size'])) {
                $file = array($file);
            }

            foreach ($file as $id => $f) {
                if ($action === self::ACTION_INSERT || $id === $identifier) {
                    $info = $this->moveFile($f, $path);
                    $filePathField->setValue($object, $info['filePath']);
                    $changes = array(
                        $config['filePathField'] => array($filePathField->getValue($object), $info['filePath'])
                    );

                    if ($config['fileMimeTypeField']) {
                        $changes[$config['fileMimeTypeField']] = array($fileMimeTypeField->getValue($object), $info['fileMimeType']);
                    }

                    if ($config['fileSizeField']) {
                        $changes[$config['fileSizeField']] = array($fileSizeField->getValue($object), $info['fileSize']);
                    }

                    $uow->scheduleExtraUpdate($object, $changes);
                    $ea->setOriginalObjectProperty($uow, spl_object_hash($object), $config['filePathField'], $info['filePath']);
                }
            }
        }
    }

    /**
     * Removes a file from an Uploadable file
     *
     * @param ClassMetadata
     * @param array
     * @param object
     *
     * @return bool
     */
    public function removeFile(ClassMetadata $meta, array $config, $object)
    {
        $refl = $meta->getReflectionClass();
        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);
        $filePath = $filePathField->getValue($object);

        return $this->doRemoveFile($filePath);
    }

    /**
     * Simple wrapper for the function "unlink" to ease testing
     *
     * @param string
     *
     * @return bool
     */
    public function doRemoveFile($filePath)
    {
        $res = false;

        if (is_file($filePath)) {
            $res = unlink($filePath);
        }

        return $res;
    }

    /**
     * Moves the file to the specified path
     *
     * @param array - File array
     * @param string - Path
     * 
     * @return array - Information about the moved file
     */
    public function moveFile(array $fileInfo, $path)
    {
        if ($fileInfo['error'] > 0) {
            switch ($fileInfo) {
                case 1:
                    $msg = 'Size of uploaded file "%s" exceeds limit imposed by directive "upload_max_filesize" in php.ini';

                    throw new UploadableIniSizeException(sprintf($msg, $fileInfo['name']));
                case 2:
                    $msg = 'Size of uploaded file "%s" exceeds limit imposed by option MAX_FILE_SIZE in your form.';

                    throw new UploadableFormSizeException(sprintf($msg, $fileInfo['name']));
                case 3:
                    $msg = 'File "%s" was partially uploaded.';

                    throw new UploadablePartialException(sprintf($msg, $fileInfo['name']));
                case 4:
                    $msg = 'No file was uploaded!';

                    throw new UploadableNoFileException(sprintf($msg, $fileInfo['name']));
                case 6:
                    $msg = 'Upload failed. Temp dir is missing.';

                    throw new UploadableNoTmpDirException($msg);
                case 7:
                    $msg = 'File "%s" couldn\'t be uploaded because directory is not writable.';

                    throw new UploadableCantWriteException(sprintf($msg, $fileInfo['name']));
                case 8:
                    $msg = 'A PHP Extension stopped the uploaded for some reason.';

                    throw new UploadableExtensionException(sprintf($msg, $fileInfo['name']));
                default:
                    throw new UploadableUploadException(sprintf('There was an unknown problem while uploading file "%s"',
                        $fileInfo['name']
                    ));
            }
        }

        $info = array(
            'fileName'      => '',
            'filePath'      => '',
            'fileMimeType'  => $fileInfo['type'],
            'fileSize'      => $fileInfo['size']
        );

        $info['fileName'] = $fileInfo['name'];
        $info['filePath'] = $path.'/'.$info['fileName'];

        if ($this->isUploadedFile($fileInfo['tmp_name']) && $this->moveUploadedFile($fileInfo['tmp_name'], $info['filePath'])) {
            return $info;
        } else {
            throw new UploadableUploadException(sprintf('File "%s" was not uploaded, or there was a problem moving it to the location "%s".',
                $fileInfo['fileName'],
                $path
            ));
        }
    }

    /**
     * Simple wrapper to "is_uploaded_file" function so we can mock it in tests
     *
     * @param string
     *
     * @return bool
     */
    public function isUploadedFile($file)
    {
        return is_uploaded_file($file);
    }

    /**
     * Simple wrapper to "move_uploaded_file" function so we can mock it in tests
     *
     * @param string - Source file
     * @param string - Destination file
     *
     * @return bool
     */
    public function moveUploadedFile($source, $dest)
    {
        if (!is_dir($dest)) {
            throw new UploadableDirectoryNotFoundException(sprintf('File "%s" cannot be moved because that directory does not exist!',
                $dest
            ));
        }

        return $this->doMoveUploadedFile($source, $dest);
    }

    /**
     * Simple wrapper to "move_uploaded_file" function so we can mock it in tests
     *
     * @param string - Source file
     * @param string - Destination file
     *
     * @return bool
     */
    public function doMoveUploadedFile($source, $dest)
    {
        return move_uploaded_file($source, $dest);
    }

    /**
     * Gets the $_FILES item at the index represented
     * by the string passed as first argument
     *
     * @param string
     * @return array
     */
    public function getFile($indexString)
    {
        $len = strlen($indexString);

        if (empty($indexString) || $indexString{0} !== '[' || $indexString{$len - 1} !== ']') {
            $msg = 'Index string "%s" is invalid. It should be something like "[image]" or "[image_form][images]';

            throw new \InvalidArgumentException(sprintf($msg), $indexString);
        }

        $indexString = substr($indexString, 1, strlen($indexString) - 2);

        if (strpos($indexString, '][') !== false) {
            $indexArray = explode('][', $indexString);
        } else {
            $indexArray = array($indexString);
        }

        return $this->getItemFromArray($_FILES, $indexArray);
    }

    /**
     * Helper method to return an item from an array using an array of indexes
     * as an index path
     *
     * @param array - Source array
     * @param array - Array of indexes
     */
    public function getItemFromArray(array $sourceArray, array $indexesArray)
    {
        // Nothing to do
        if (empty($sourceArray)) {
            return false;
        }

        if (empty($indexesArray)) {
            $msg = 'Second argument: "indexesArray" must be a non empty array with the indexes to search.';

            throw new \InvalidArgumentException($msg);
        }

        if (isset($sourceArray[$indexesArray[0]])) {
            if (count($indexesArray) === 1) {
                return $sourceArray[$indexesArray[0]];
            } else {
                $source = $sourceArray[$indexesArray[0]];

                if (!is_array($source)) {
                    return false;
                }

                unset($sourceArray[$indexesArray[0]]);
                $indexesArray = array_slice($indexesArray, 1);

                return $this->getItemFromArray($source, $indexesArray);
            }
        } else {
            return false;
        }
    }

    /**
     * Maps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}