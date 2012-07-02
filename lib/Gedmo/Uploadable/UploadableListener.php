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
    Gedmo\Exception\UploadableMaxSizeException,
    Gedmo\Exception\UploadableInvalidMimeTypeException,
    Gedmo\Exception\UploadableCouldntGuessMimeTypeException,
    Gedmo\Uploadable\Mapping\Validator,
    Gedmo\Uploadable\FileInfo\FileInfoInterface,
    Gedmo\Uploadable\FileInfo\FileInfoArray,
    Gedmo\Uploadable\MimeType\MimeTypeGuesser,
    Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface,
    Gedmo\Uploadable\MimeType\MimeTypesExtensionsMap;

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
     * Mime type guesser
     *
     * @var Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

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
     * Array of FileInfoInterface objects. The index is the hash of the entity owner
     * of the FileInfoInterface object.
     *
     * @var array
     */
    private $fileInfoObjects = array();



    public function __construct(\MimeTypeGuesserInterface $mimeTypeGuesser = null)
    {
        $this->mimeTypeGuesser = $mimeTypeGuesser ? $mimeTypeGuesser : new MimeTypeGuesser();
    }

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
        $oid = spl_object_hash($object);

        if (!isset($this->fileInfoObjects[$oid])) {
            // Nothing to do

            return;
        }

        $fileInfo = $this->fileInfoObjects[$oid];

        // Validations
        if ($config['maxSize'] > 0 && $fileInfo->getSize() > $config['maxSize']) {
            $msg = 'File "%s" exceeds the maximum allowed size of %d bytes. File size: %d bytes';

            throw new UploadableMaxSizeException(sprintf($msg,
                $fileInfo->getName(),
                $config['maxSize'],
                $fileInfo->getSize()
            ));
        }

        $mime = $this->mimeTypeGuesser->guess($fileInfo->getTmpName());

        if (!$mime) {
            throw new UploadableCouldntGuessMimeTypeException(sprintf('Couldn\'t guess mime type for file "%s".',
                $fileInfo->getName()
            ));
        }

        if ($config['allowedTypes'] || $config['disallowedTypes']) {
            $ok = $config['allowedTypes'] ? false : true;
            $mimes = $config['allowedTypes'] ? $config['allowedTypes'] : $config['disallowedTypes'];

            foreach ($mimes as $m) {
                if ($mime === $m) {
                    $ok = $config['allowedTypes'] ? true : false;

                    break;
                }
            }

            if (!$ok) {
                throw new UploadableInvalidMimeTypeException(sprintf('Invalid mime type "%s" for file "%s".',
                    $mime,
                    $fileInfo->getName()
                ));
            }
        }

        $filePathField = $refl->getProperty($config['filePathField']);
        $filePathField->setAccessible(true);

        $path = $config['path'];

        if ($path === '') {
            if ($config['pathMethod'] !== '') {
                $pathMethod = $refl->getMethod($config['pathMethod']);
                $pathMethod->setAccessible(true);
                $path = $pathMethod->invoke($object);
            } else if ($this->getDefaultPath() !== null) {
                $path = $this->getDefaultPath();
            } else {
                $msg = 'You have to define the path to save files either in the listener, or in the class "%s"';

                throw new UploadableNoPathDefinedException(sprintf($msg,
                    $meta->name
                ));
            }
        }

        Validator::validatePath($path);

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

        // We generate the filename based on configuration
        $generatorNamespace = 'Gedmo\Uploadable\FilenameGenerator';

        switch ($config['filenameGenerator']) {
            case Validator::FILENAME_GENERATOR_ALPHANUMERIC:
                $generatorClass = $generatorNamespace .'\FilenameGeneratorAlphanumeric';

                break;
            case Validator::FILENAME_GENERATOR_SHA1:
                $generatorClass = $generatorNamespace .'\FilenameGeneratorSha1';

                break;
            case Validator::FILENAME_GENERATOR_NONE:
                $generatorClass = false;

                break;
            default:
                $generatorClass = $config['filenameGenerator'];
        }

        $info = $this->moveFile($fileInfo, $path, $generatorClass, $config['allowOverwrite'], $config['appendNumber']);

        // We override the mime type with the guessed one
        $info['fileMimeType'] = $mime;

        $filePathField->setValue($object, $info['filePath']);

        if ($config['callback'] !== '') {
            $callbackMethod = $refl->getMethod($config['callback']);
            $callbackMethod->setAccessible(true);

            $callbackMethod->invokeArgs($object, array($info));
        }

        $changes = array(
            $config['filePathField'] => array($filePathField->getValue($object), $info['filePath'])
        );

        if ($config['fileMimeTypeField']) {
            $changes[$config['fileMimeTypeField']] = array($fileMimeTypeField->getValue($object), $info['fileMimeType']);
            $ea->setOriginalObjectProperty($uow, $oid, $config['fileMimeTypeField'], $info['fileMimeType']);
        }

        if ($config['fileSizeField']) {
            $changes[$config['fileSizeField']] = array($fileSizeField->getValue($object), $info['fileSize']);
            $ea->setOriginalObjectProperty($uow, $oid, $config['fileSizeField'], $info['fileSize']);
        }

        $uow->scheduleExtraUpdate($object, $changes);
        $ea->setOriginalObjectProperty($uow, $oid, $config['filePathField'], $info['filePath']);

        unset($this->fileInfoObjects[$oid]);
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

     * @param FileInfoInterface
     * @param string - Path
     * @param mixed - String of class that implements FilenameGeneratorInterface, or false
     * @param bool - Overwrite if file already exists?
     * @param bool - Append a number if file already exists?
     *
     * @return array - Information about the moved file
     */
    public function moveFile(FileInfoInterface $fileInfo, $path, $filenameGeneratorClass = false, $overwrite = false, $appendNumber = false)
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
            'fileName'          => '',
            'fileExtension'     => '',
            'fileWithoutExt'    => '',
            'filePath'          => '',
            'fileMimeType'      => $fileInfo->getType(),
            'fileSize'          => $fileInfo->getSize()
        );

        $info['fileName'] = basename($fileInfo->getName());
        $info['filePath'] = $path.'/'.$info['fileName'];

        $hasExtension = strrpos($info['fileName'], '.');

        if ($hasExtension) {
            $info['fileExtension'] = substr($info['filePath'], strrpos($info['filePath'], '.'));
            $info['fileWithoutExt'] = substr($info['filePath'], 0, strrpos($info['filePath'], '.'));
        } else {
            $info['fileWithoutExt'] = $info['fileName'];
        }

        // Now we generate the filename using the configured class
        if ($filenameGeneratorClass) {
            $filename = $filenameGeneratorClass::generate(
                str_replace($path.'/', '', $info['fileWithoutExt']),
                $info['fileExtension']
            );
            $info['filePath'] = str_replace(
                $info['fileName'],
                $filename,
                $info['filePath']
            );
            $info['fileName'] = $filename;
        }

        if (is_file($info['filePath'])) {
            if ($overwrite) {
                $this->removeFile($info['filePath']);
            } else if ($appendNumber) {
                $counter = 1;
                $info['filePath'] = $info['fileWithoutExt'].'-'.$counter.$info['fileExtension'];

                do {
                    $info['filePath'] = $info['fileWithoutExt'].'-'.(++$counter).$info['fileExtension'];
                } while (is_file($info['filePath']));
            } else {
                throw new UploadableFileAlreadyExistsException(sprintf('File "%s" already exists!',
                    $info['filePath']
                ));
            }
        }

        if (!$this->doMoveFile($fileInfo->getTmpName(), $info['filePath'], $fileInfo->isUploadedFile())) {
            throw new UploadableUploadException(sprintf('File "%s" was not uploaded, or there was a problem moving it to the location "%s".',
                $fileInfo->getName(),
                $path
            ));
        }

        return $info;
    }

    /**
     * Simple wrapper method used to move the file. If it's an uploaded file
     * it will use the "move_uploaded_file method. If it's not, it will
     * simple move it
     *
     * @param string - Source file
     * @param string - Destination file
     * @param bool - Is an uploaded file?
     *
     * @return bool
     */
    public function doMoveFile($source, $dest, $isUploadedFile = true)
    {
        return $isUploadedFile ? move_uploaded_file($source, $dest) : copy($source, $dest);
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
     * Adds a FileInfoInterface object for the given entity
     *
     * @param object - $entity
     * @param FileInfo\FileInfoInterface $fileInfo
     */
    public function addEntityFileInfo($entity, $fileInfo)
    {
        $fileInfoClass = $this->getDefaultFileInfoClass();
        $fileInfo = is_array($fileInfo) ? new $fileInfoClass($fileInfo) : $fileInfo;

        if (!is_object($fileInfo) || !($fileInfo instanceof FileInfoInterface)) {
            $msg = 'You must pass an instance of FileInfoInterface or a valid array for entity of class "%s".';

            throw new \RuntimeException(sprintf($msg,
                get_class($entity)
            ));
        }

        $this->fileInfoObjects[spl_object_hash($entity)] = $fileInfo;
    }

    public function getEntityFileInfo($entity)
    {
        $oid = spl_object_hash($entity);

        if (!isset($this->fileInfoObjects[$oid])) {
            throw new \RuntimeException(sprintf('There\'s no FileInfoInterface object for entity of class "%s".',
                get_class($entity)
            ));
        }

        return $this->fileInfoObjects[$oid];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @param \Gedmo\Uploadable\Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
     */
    public function setMimeTypeGuesser(MimeTypeGuesserInterface $mimeTypeGuesser)
    {
        $this->mimeTypeGuesser = $mimeTypeGuesser;
    }

    /**
     * @return \Gedmo\Uploadable\Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface
     */
    public function getMimeTypeGuesser()
    {
        return $this->mimeTypeGuesser;
    }
}