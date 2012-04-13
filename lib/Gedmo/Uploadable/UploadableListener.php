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
    Gedmo\Exception\UploadableNoPathDefinedException,
    Gedmo\Uploadable\Mapping\Validator,
    Gedmo\Uploadable\FileInfo\FileInfoInterface,
    Gedmo\Uploadable\FileInfo\FileInfoArray;

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
     * Default path to move files in
     *
     * @var string
     */
    private $defaultPath;

    /**
     * Default FileInfoInterface class
     *
     * @var string
     */
    private $defaultFileInfoClass = 'Gedmo\Uploadable\FileInfo\FileInfoArray';

    /**
     * Array of files to remove on postFlush
     *
     * @var array
     */
    private $pendingFileRemovals = array();

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'onFlush',
            'postFlush'
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
                    $this->pendingFileRemovals[] = $this->getFilePath($meta, $config, $object);
                }
            }
        }
    }

    /**
     * Handle removal of files
     *
     * @param EventArgs
     *
     * @return void
     */
    public function postFlush(EventArgs $args)
    {
        if (!empty($this->pendingFileRemovals)) {
            foreach ($this->pendingFileRemovals as $file) {
                $this->removeFile($file);
            }

            $this->pendingFileRemovals = array();
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
        $fileInfo = $fileInfoProp->getValue($object);

        if (!$fileInfo) {
            // Nothing to do

            return;
        }

        $fileInfoClass = $this->getDefaultFileInfoClass();
        $fileInfo = is_array($fileInfo) ? new $fileInfoClass($fileInfo) : $fileInfo;
        $fileInfoProp->setValue($object, $fileInfo);

        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);

        if (!($fileInfo instanceof FileInfoInterface)) {
            $msg = 'Property "%s" for class "%s" must contain either an array with information ';
            $msg .= 'about an uploaded or a FileInfoInterface instance. ';

            throw new \RuntimeException(sprintf($msg,
                $fileInfoProp->getName(),
                $meta->name
            ));
        }

        $path = $config['path'];

        if ($path === '') {
            if ($config['pathMethod'] !== '') {
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
            } else if ($this->getDefaultPath() !== null) {
                $path = $this->getDefaultPath();
            } else {
                $msg = 'You have to define the path to save files either in the listener, or in the class "%s"';

                throw new UploadableNoPathDefinedException(sprintf($msg,
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

        if ($action === self::ACTION_UPDATE) {
            // First we add the original file to the pendingFileRemovals array
            $this->pendingFileRemovals[] = $this->getFilePath($meta, $config, $object);
        }

        $info = $this->moveFile($fileInfo, $path, $config['allowOverwrite'], $config['appendNumber']);
        $filePathField->setValue($object, $info['filePath']);

        if ($config['callback'] !== '') {
            $callbackMethod = $refl->getMethod($config['callback']);
            $callbackMethod->setAccessible(true);

            $callbackMethod->invokeArgs($object, array($config));
        }

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

        $oid = spl_object_hash($object);
        $ea->setOriginalObjectProperty($uow, $oid, $config['filePathField'], $info['filePath']);
    }

    /**
     * Returns the path of the entity's file
     *
     * @param ClassMetadata
     * @param array - Configuration
     * @param object - Entity
     *
     * @return string
     */
    public function getFilePath(ClassMetadata $meta, array $config, $object)
    {
        $refl = $meta->getReflectionClass();
        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);
        $filePath = $filePathField->getValue($object);

        return $filePath;
    }

    /**
     * Simple wrapper for the function "unlink" to ease testing
     *
     * @param string
     *
     * @return bool
     */
    public function removeFile($filePath)
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
    public function moveFile(FileInfoInterface $fileInfo, $path, $overwrite = false, $appendNumber = false)
    {
        if ($fileInfo->getError() > 0) {
            switch ($fileInfo->getError()) {
                case 1:
                    $msg = 'Size of uploaded file "%s" exceeds limit imposed by directive "upload_max_filesize" in php.ini';

                    throw new UploadableIniSizeException(sprintf($msg, $fileInfo->getName()));
                case 2:
                    $msg = 'Size of uploaded file "%s" exceeds limit imposed by option MAX_FILE_SIZE in your form.';

                    throw new UploadableFormSizeException(sprintf($msg, $fileInfo->getName()));
                case 3:
                    $msg = 'File "%s" was partially uploaded.';

                    throw new UploadablePartialException(sprintf($msg, $fileInfo->getName()));
                case 4:
                    $msg = 'No file was uploaded!';

                    throw new UploadableNoFileException(sprintf($msg, $fileInfo->getName()));
                case 6:
                    $msg = 'Upload failed. Temp dir is missing.';

                    throw new UploadableNoTmpDirException($msg);
                case 7:
                    $msg = 'File "%s" couldn\'t be uploaded because directory is not writable.';

                    throw new UploadableCantWriteException(sprintf($msg, $fileInfo->getName()));
                case 8:
                    $msg = 'A PHP Extension stopped the uploaded for some reason.';

                    throw new UploadableExtensionException(sprintf($msg, $fileInfo->getName()));
                default:
                    throw new UploadableUploadException(sprintf('There was an unknown problem while uploading file "%s"',
                        $fileInfo->getName()
                    ));
            }
        }

        $info = array(
            'fileName'      => '',
            'filePath'      => '',
            'fileMimeType'  => $fileInfo->getType(),
            'fileSize'      => $fileInfo->getSize()
        );

        $info['fileName'] = basename($fileInfo->getName());
        $info['filePath'] = $path.'/'.$info['fileName'];

        if (is_file($info['filePath'])) {
            if ($overwrite) {
                $this->removeFile($info['filePath']);
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

        if (!$this->moveUploadedFile($fileInfo->getTmpName(), $info['filePath'])) {
            throw new UploadableUploadException(sprintf('File "%s" was not uploaded, or there was a problem moving it to the location "%s".',
                $fileInfo['fileName'],
                $path
            ));
        }

        return $info;
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
     * Sets the default path
     *
     * @param string
     *
     * @return void
     */
    public function setDefaultPath($path)
    {
        $this->defaultPath = $path;
    }

    /**
     * Returns default path
     *
     * @return string
     */
    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    /**
     * Sets file info default class
     *
     * @param string
     *
     * @return void
     */
    public function setDefaultFileInfoClass($defaultFileInfoClass)
    {
        $this->defaultFileInfoClass = $defaultFileInfoClass;
    }

    /**
     * Returns file info default class
     *
     * @return string
     */
    public function getDefaultFileInfoClass()
    {
        return $this->defaultFileInfoClass;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}