<?php

namespace Gedmo\Uploadable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\EventArgs;
use Doctrine\Common\NotifyPropertyChanged;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\UploadablePartialException;
use Gedmo\Exception\UploadableCantWriteException;
use Gedmo\Exception\UploadableExtensionException;
use Gedmo\Exception\UploadableFormSizeException;
use Gedmo\Exception\UploadableIniSizeException;
use Gedmo\Exception\UploadableNoFileException;
use Gedmo\Exception\UploadableNoTmpDirException;
use Gedmo\Exception\UploadableUploadException;
use Gedmo\Exception\UploadableFileAlreadyExistsException;
use Gedmo\Exception\UploadableNoPathDefinedException;
use Gedmo\Exception\UploadableMaxSizeException;
use Gedmo\Exception\UploadableInvalidMimeTypeException;
use Gedmo\Exception\UploadableCouldntGuessMimeTypeException;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\FileInfo\FileInfoInterface;
use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;
use Gedmo\Uploadable\Events;
use Gedmo\Uploadable\Event\UploadablePreFileProcessEventArgs;
use Gedmo\Uploadable\Event\UploadablePostFileProcessEventArgs;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Uploadable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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



    public function __construct(MimeTypeGuesserInterface $mimeTypeGuesser = null)
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
            'preFlush',
            'onFlush',
            'postFlush'
        );
    }

    /**
     * This event is needed in special cases where the entity needs to be updated, but it only has the
     * file field modified. Since we can't mark an entity as "dirty" in the "addEntityFileInfo" method,
     * doctrine thinks the entity has no changes, which produces that the "onFlush" event gets never called.
     * Here we mark the entity as dirty, so the "onFlush" event gets called, and the file is processed.
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function preFlush(EventArgs $event)
    {
        if (empty($this->fileInfoObjects)) {
            // Nothing to do
            return;
        }

        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();
        $first = reset($this->fileInfoObjects);
        $meta = $om->getClassMetadata(get_class($first['entity']));
        $config = $this->getConfiguration($om, $meta->name);

        foreach ($this->fileInfoObjects as $info) {
            $entity = $info['entity'];

            // If the entity is in the identity map, it means it will be updated. We need to force the
            // "dirty check" here by "modifying" the path. We are actually setting the same value, but
            // this will mark the entity as dirty, and the "onFlush" event will be fired, even if there's
            // no other change in the entity's fields apart from the file itself.
            if ($uow->isInIdentityMap($entity)) {
                $path = $this->getFilePath($meta, $config, $entity);

                $uow->propertyChanged($entity, $config['filePathField'], $path, $path);
                $uow->scheduleForUpdate($entity);
            }
        }
    }

    /**
     * Handle file-uploading depending on the action
     * being done with objects
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        // Do we need to upload files?
        foreach ($this->fileInfoObjects as $info) {
            $entity = $info['entity'];
            $scheduledForInsert = $uow->isScheduledForInsert($entity);
            $scheduledForUpdate = $uow->isScheduledForUpdate($entity);
            $action = ($scheduledForInsert || $scheduledForUpdate) ?
                ($scheduledForInsert ? self::ACTION_INSERT : self::ACTION_UPDATE) :
                false;

            if ($action) {
                $this->processFile($om, $entity, $action);
            }
        }

        // Do we need to remove any files?
        foreach (OMH::getScheduledObjectDeletions($uow) as $object) {
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
     * @param \Doctrine\Common\EventArgs $event
     */
    public function postFlush(EventArgs $event)
    {
        if (!empty($this->pendingFileRemovals)) {
            foreach ($this->pendingFileRemovals as $file) {
                $this->removeFile($file);
            }

            $this->pendingFileRemovals = array();
        }

        $this->fileInfoObjects = array();
    }

    /**
     * If it's a Uploadable object, verify if the file was uploaded.
     * If that's the case, process it.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param $object
     * @param $action
     * @throws \Gedmo\Exception\UploadableNoPathDefinedException
     * @throws \Gedmo\Exception\UploadableCouldntGuessMimeTypeException
     * @throws \Gedmo\Exception\UploadableMaxSizeException
     * @throws \Gedmo\Exception\UploadableInvalidMimeTypeException
     */
    public function processFile(ObjectManager $om, $object, $action)
    {
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        if (!$config || !isset($config['uploadable']) || !$config['uploadable']) {
            // Nothing to do
            return;
        }

        $refl = $meta->getReflectionClass();
        $fileInfo = $this->fileInfoObjects[$oid]['fileInfo'];
        $evm = $om->getEventManager();

        if ($evm->hasListeners(Events::uploadablePreFileProcess)) {
            $evm->dispatchEvent(Events::uploadablePreFileProcess, new UploadablePreFileProcessEventArgs(
                $this,
                $om,
                $config,
                $fileInfo,
                $object,
                $action
            ));
        }

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
            $defaultPath = $this->getDefaultPath();
            if ($config['pathMethod'] !== '') {
                $pathMethod = $refl->getMethod($config['pathMethod']);
                $pathMethod->setAccessible(true);
                $path = $pathMethod->invokeArgs($object, array($defaultPath));
            } else if ($defaultPath !== null) {
                $path = $defaultPath;
            } else {
                $msg = 'You have to define the path to save files either in the listener, or in the class "%s"';

                throw new UploadableNoPathDefinedException(sprintf($msg,
                    $meta->name
                ));
            }
        }

        Validator::validatePath($path);
        $path = rtrim($path, '\/');

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

        $info = $this->moveFile($fileInfo, $path, $generatorClass, $config['allowOverwrite'], $config['appendNumber'], $object);

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

            $this->updateField($om, $object, $config['fileMimeTypeField'], $info['fileMimeType']);
        }

        if ($config['fileSizeField']) {
            $changes[$config['fileSizeField']] = array($fileSizeField->getValue($object), $info['fileSize']);

            $this->updateField($om, $object, $config['fileSizeField'], $info['fileSize']);
        }

        $this->updateField($om, $object, $config['filePathField'], $info['filePath']);

        OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);

        if ($evm->hasListeners(Events::uploadablePostFileProcess)) {
            $evm->dispatchEvent(Events::uploadablePostFileProcess, new UploadablePostFileProcessEventArgs(
                $this,
                $om,
                $config,
                $fileInfo,
                $object,
                $action
            ));
        }

        unset($this->fileInfoObjects[$oid]);
    }

    /**
     * Returns the path of the entity's file
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param array $config
     * @param $object
     * @return mixed
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
            return @unlink($filePath);
        }

        return false;
    }

    /**
     * Moves the file to the specified path
     *
     * @param FileInfo\FileInfoInterface $fileInfo
     * @param $path
     * @param bool $filenameGeneratorClass
     * @param bool $overwrite
     * @param bool $appendNumber
     * @param $object
     * @return array
     * @throws \Gedmo\Exception\UploadableUploadException
     * @throws \Gedmo\Exception\UploadableNoFileException
     * @throws \Gedmo\Exception\UploadableExtensionException
     * @throws \Gedmo\Exception\UploadableIniSizeException
     * @throws \Gedmo\Exception\UploadableFormSizeException
     * @throws \Gedmo\Exception\UploadableFileAlreadyExistsException
     * @throws \Gedmo\Exception\UploadablePartialException
     * @throws \Gedmo\Exception\UploadableNoTmpDirException
     * @throws \Gedmo\Exception\UploadableCantWriteException
     */
    public function moveFile(FileInfoInterface $fileInfo, $path, $filenameGeneratorClass = false, $overwrite = false, $appendNumber = false, $object)
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
            'fileName'          => basename($fileInfo->getName()),
            'fileExtension'     => '',
            'fileWithoutExt'    => '',
            'origFileName'      => '',
            'filePath'          => '',
            'fileMimeType'      => $fileInfo->getType(),
            'fileSize'          => $fileInfo->getSize()
        );

        $info['filePath'] = $path.'/'.$info['fileName'];
        $info['fileWithoutExt'] = pathinfo($info['fileName'], PATHINFO_FILENAME);
        $info['fileExtension'] = pathinfo($info['fileName'], PATHINFO_EXTENSION);

        // Save the original filename for later use
        $info['origFileName'] = $info['fileName'];

        // Now we generate the filename using the configured class
        if ($filenameGeneratorClass) {
            $info['fileName'] = $filenameGeneratorClass::generate(
                $info['fileWithoutExt'],
                $info['fileExtension'],
                $object
            );

            $info['filePath'] = $path.'/'.$info['fileName'];
            $info['fileWithoutExt'] = pathinfo($info['fileName'], PATHINFO_FILENAME);
        }

        if (is_file($info['filePath'])) {
            if ($overwrite) {
                $this->removeFile($info['filePath']);
            } else if ($appendNumber) {
                $counter = 1;
                $info['filePath'] = $path.'/'.$info['fileWithoutExt'].'-'.$counter.'.'.$info['fileExtension'];

                do {
                    $info['filePath'] = $path.'/'.$info['fileWithoutExt'].'-'.(++$counter).'.'.$info['fileExtension'];
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
        return $isUploadedFile ? @move_uploaded_file($source, $dest) : @copy($source, $dest);
    }

    /**
     * Maps additional metadata
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
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
        $fileInfoInterface = 'Gedmo\\Uploadable\\FileInfo\\FileInfoInterface';
        $refl = is_string($defaultFileInfoClass) && class_exists($defaultFileInfoClass) ?
            new \ReflectionClass($defaultFileInfoClass) :
            false;

        if (!$refl || !$refl->implementsInterface($fileInfoInterface)) {
            $msg = sprintf('Default FileInfo class must be a valid class, and it must implement "%s".',
                $fileInfoInterface
            );

            throw new \Gedmo\Exception\InvalidArgumentException($msg);
        }

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
     * @param $entity
     * @param $fileInfo
     * @throws \RuntimeException
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

        $this->fileInfoObjects[spl_object_hash($entity)] = array(
            'entity'        => $entity,
            'fileInfo'      => $fileInfo
        );
    }

    public function getEntityFileInfo($entity)
    {
        $oid = spl_object_hash($entity);

        if (!isset($this->fileInfoObjects[$oid])) {
            throw new \RuntimeException(sprintf('There\'s no FileInfoInterface object for entity of class "%s".',
                get_class($entity)
            ));
        }

        return $this->fileInfoObjects[$oid]['fileInfo'];
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

    protected function updateField(ObjectManager $om, $object, $field, $value, $notifyPropertyChanged = true)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $property->setValue($object, $value);

        if ($notifyPropertyChanged && $object instanceof NotifyPropertyChanged) {
            $uow = $om->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $value);
        }
    }
}
