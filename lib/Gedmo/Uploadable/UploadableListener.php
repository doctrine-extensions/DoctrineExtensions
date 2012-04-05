<?php

namespace Gedmo\Uploadable;

use Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\UnitOfWork,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\Common\EventArgs,
    Gedmo\Mapping\Event\AdapterInterface,
    Gedmo\Exception\UploadableDirectoryNotFoundException,
    Gedmo\Exception\UploadablePartialException,
    Gedmo\Exception\UploadableCantWriteException,
    Gedmo\Exception\UploadableExtensionException,
    Gedmo\Exception\UploadableFormSizeException,
    Gedmo\Exception\UploadableIniSizeException,
    Gedmo\Exception\UploadableNoFileException,
    Gedmo\Exception\UploadableNoTmpDirException,
    Gedmo\Exception\UploadableUploadException,
    Gedmo\Exception\UploadableFileAlreadyExistsException,
    Gedmo\Uploadable\Mapping\Validator;

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
     * @param EventArgs
     *
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
                    $this->processFile($uow, $ea, $meta, $config, $object, self::ACTION_INSERT);
                }
            }
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));

            if ($config = $this->getConfiguration($om, $meta->name)) {
                if (isset($config['uploadable']) && $config['uploadable']) {
                    $this->processFile($uow, $ea, $meta, $config, $object, self::ACTION_UPDATE);
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
     * @param UnitOfWork
     * @param AdapterInterface
     * @param ClassMetadata
     * @param array - Configuration
     * @param object - The entity
     * @param string - String representing the action (insert or update)
     *
     * @return void
     */
    public function processFile(UnitOfWork $uow, AdapterInterface $ea, ClassMetadata $meta, array $config, $object, $action)
    {
        $refl = $meta->getReflectionClass();
        $fileInfoProp = $refl->getProperty($config['fileInfoProperty']);
        $fileInfoProp->setAccessible(true);
        $file = $fileInfoProp->getValue($object);
        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);

        $path = $config['path'];

        if ($path === '') {
            $pathMethod = $refl->getMethod($config['pathMethod']);
            $pathMethod->setAccessible(true);
            $path = $pathMethod->invoke($object);

            if (is_string($path) && $path !== '') {
                Validator::validatePath($path);
            } else {
                $msg = 'The method which returns the file path in class "%s" must return a valid path.';

                throw new \RuntimeException(sprintf($msg,
                    $meta->name
                ));
            }
        }

        $path = substr($path, strlen($path) - 1) === '/' ? substr($path, 0, strlen($path) - 2) : $path;

        if ($config['fileMimeTypeField']) {
            $fileMimeTypeField = $refl->getProperty($config['fileMimeTypeField']);
            $fileMimeTypeField->setAccessible(true);
        }

        if ($config['fileSizeField']) {
            $fileSizeField = $refl->getProperty($config['fileSizeField']);
            $fileSizeField->setAccessible(true);
        }

        if (is_array($file)) {
            // If it's a single file we create an array anyway, so we can process
            // a collection or a single file in the same way
            if (isset($file['size'])) {
                $file = array($file);
            }

            foreach ($file as $f) {
                // First we remove the original file
                $this->removefile($meta, $config, $object);

                $info = $this->moveFile($f, $path, $config['allowOverwrite'], $config['appendNumber']);
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

    /**
     * Removes a file from an Uploadable file
     *
     * @param ClassMetadata
     * @param array - Configuration
     * @param object - Entity
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
        if (is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Moves the file to the specified path
     *
     * @param array - File array
     * @param string - Path
     * 
     * @return array - Information about the moved file
     */
    public function moveFile(array $fileInfo, $path, $overwrite = false, $appendNumber = false)
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

        $info['fileName'] = basename($fileInfo['name']);
        $info['filePath'] = $path.'/'.$info['fileName'];

        if (is_file($info['filePath'])) {
            if ($overwrite) {
                $this->doRemoveFile($info['filePath']);
            } else if ($appendNumber) {
                $counter = 1;
                $extensionPos = strrpos($info['filePath'], '.');

                if ($extensionPos !== false) {
                    $extension = substr($info['filePath'], $extensionPos);

                    $fileWithoutExt = substr($info['filePath'], 0, strrpos($info['filePath'], '.'));

                    $info['filePath'] = $fileWithoutExt.'-'.$counter.$extension;
                }

                do {
                    $info['filePath'] = $fileWithoutExt.'-'.(++$counter).$extension;
                } while (is_file($info['filePath']));
            } else {
                throw new UploadableFileAlreadyExistsException(sprintf('File "%s" already exists!',
                    $info['filePath']
                ));
            }
        }

        if ($this->moveUploadedFile($fileInfo['tmp_name'], $info['filePath'])) {
            return $info;
        } else {
            throw new UploadableUploadException(sprintf('File "%s" was not uploaded, or there was a problem moving it to the location "%s".',
                $fileInfo['fileName'],
                $path
            ));
        }
    }

    /**
     * Simple wrapper to "move_uploaded_file" function to ease testing
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
     * Simple wrapper to "move_uploaded_file" function to ease testing
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
     * Maps additional metadata
     *
     * @param EventArgs
     *
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