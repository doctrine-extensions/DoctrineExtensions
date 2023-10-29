<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UploadableCantWriteException;
use Gedmo\Exception\UploadableCouldntGuessMimeTypeException;
use Gedmo\Exception\UploadableExtensionException;
use Gedmo\Exception\UploadableFileAlreadyExistsException;
use Gedmo\Exception\UploadableFormSizeException;
use Gedmo\Exception\UploadableIniSizeException;
use Gedmo\Exception\UploadableInvalidMimeTypeException;
use Gedmo\Exception\UploadableMaxSizeException;
use Gedmo\Exception\UploadableNoFileException;
use Gedmo\Exception\UploadableNoPathDefinedException;
use Gedmo\Exception\UploadableNoTmpDirException;
use Gedmo\Exception\UploadablePartialException;
use Gedmo\Exception\UploadableUploadException;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Uploadable\Event\UploadablePostFileProcessEventArgs;
use Gedmo\Uploadable\Event\UploadablePreFileProcessEventArgs;
use Gedmo\Uploadable\FileInfo\FileInfoArray;
use Gedmo\Uploadable\FileInfo\FileInfoInterface;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;

/**
 * Uploadable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class UploadableListener extends MappedEventSubscriber
{
    public const ACTION_INSERT = 'INSERT';
    public const ACTION_UPDATE = 'UPDATE';

    /**
     * Default path to move files in
     *
     * @var string
     */
    private $defaultPath;

    /**
     * Mime type guesser
     */
    private MimeTypeGuesserInterface $mimeTypeGuesser;

    /**
     * Default FileInfoInterface class
     *
     * @phpstan-var class-string<FileInfoInterface>
     */
    private string $defaultFileInfoClass = FileInfoArray::class;

    /**
     * Array of files to remove on postFlush
     *
     * @var array<int, string>
     */
    private $pendingFileRemovals = [];

    /**
     * Array of FileInfoInterface objects. The index is the hash of the entity owner
     * of the FileInfoInterface object.
     *
     * @var array<int, array<string, object>>
     *
     * @phpstan-var array<int, array{entity: object, fileInfo: FileInfoInterface}>
     */
    private $fileInfoObjects = [];

    public function __construct(?MimeTypeGuesserInterface $mimeTypeGuesser = null)
    {
        parent::__construct();

        $this->mimeTypeGuesser = $mimeTypeGuesser ?? new MimeTypeGuesser();
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'preFlush',
            'onFlush',
            'postFlush',
        ];
    }

    /**
     * This event is needed in special cases where the entity needs to be updated, but it only has the
     * file field modified. Since we can't mark an entity as "dirty" in the "addEntityFileInfo" method,
     * doctrine thinks the entity has no changes, which produces that the "onFlush" event gets never called.
     * Here we mark the entity as dirty, so the "onFlush" event gets called, and the file is processed.
     *
     * @return void
     */
    public function preFlush(EventArgs $args)
    {
        if ([] === $this->fileInfoObjects) {
            // Nothing to do
            return;
        }

        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($this->fileInfoObjects as $info) {
            $entity = $info['entity'];
            $meta = $om->getClassMetadata(get_class($entity));
            $config = $this->getConfiguration($om, $meta->getName());

            // If the entity is in the identity map, it means it will be updated. We need to force the
            // "dirty check" here by "modifying" the path. We are actually setting the same value, but
            // this will mark the entity as dirty, and the "onFlush" event will be fired, even if there's
            // no other change in the entity's fields apart from the file itself.
            if ($uow->isInIdentityMap($entity)) {
                if ($config['filePathField']) {
                    $path = $this->getFilePathFieldValue($meta, $config, $entity);
                    $uow->propertyChanged($entity, $config['filePathField'], $path, $path);
                } else {
                    $fileName = $this->getFileNameFieldValue($meta, $config, $entity);
                    $uow->propertyChanged($entity, $config['fileNameField'], $fileName, $fileName);
                }
                $uow->scheduleForUpdate($entity);
            }
        }
    }

    /**
     * Handle file-uploading depending on the action
     * being done with objects
     *
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
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
                $this->processFile($ea, $entity, $action);
            }
        }

        // Do we need to remove any files?
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));

            if ($config = $this->getConfiguration($om, $meta->getName())) {
                if (isset($config['uploadable']) && $config['uploadable']) {
                    $this->addFileRemoval($meta, $config, $object);
                }
            }
        }
    }

    /**
     * Handle removal of files
     *
     * @return void
     */
    public function postFlush(EventArgs $args)
    {
        if ([] !== $this->pendingFileRemovals) {
            foreach ($this->pendingFileRemovals as $file) {
                $this->removeFile($file);
            }

            $this->pendingFileRemovals = [];
        }

        $this->fileInfoObjects = [];
    }

    /**
     * If it's a Uploadable object, verify if the file was uploaded.
     * If that's the case, process it.
     *
     * @param object $object
     * @param string $action
     *
     * @throws UploadableNoPathDefinedException
     * @throws UploadableCouldntGuessMimeTypeException
     * @throws UploadableMaxSizeException
     * @throws UploadableInvalidMimeTypeException
     *
     * @return void
     */
    public function processFile(AdapterInterface $ea, $object, $action)
    {
        $oid = spl_object_id($object);
        $om = $ea->getObjectManager();
        \assert($om instanceof EntityManagerInterface);
        $uow = $om->getUnitOfWork();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());

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

            throw new UploadableMaxSizeException(sprintf($msg, $fileInfo->getName(), $config['maxSize'], $fileInfo->getSize()));
        }

        $mime = $this->mimeTypeGuesser->guess($fileInfo->getTmpName());

        if (null === $mime) {
            throw new UploadableCouldntGuessMimeTypeException(sprintf('Couldn\'t guess mime type for file "%s".', $fileInfo->getName()));
        }

        if ($config['allowedTypes'] || $config['disallowedTypes']) {
            $ok = $config['allowedTypes'] ? false : true;
            $mimes = $config['allowedTypes'] ?: $config['disallowedTypes'];

            foreach ($mimes as $m) {
                if ($mime === $m) {
                    $ok = $config['allowedTypes'] ? true : false;

                    break;
                }
            }

            if (!$ok) {
                throw new UploadableInvalidMimeTypeException(sprintf('Invalid mime type "%s" for file "%s".', $mime, $fileInfo->getName()));
            }
        }

        $path = $this->getPath($meta, $config, $object);

        if (self::ACTION_UPDATE === $action) {
            // First we add the original file to the pendingFileRemovals array
            $this->addFileRemoval($meta, $config, $object);
        }

        // We generate the filename based on configuration
        $generatorNamespace = 'Gedmo\Uploadable\FilenameGenerator';

        switch ($config['filenameGenerator']) {
            case Validator::FILENAME_GENERATOR_ALPHANUMERIC:
                $generatorClass = $generatorNamespace.'\FilenameGeneratorAlphanumeric';

                break;
            case Validator::FILENAME_GENERATOR_SHA1:
                $generatorClass = $generatorNamespace.'\FilenameGeneratorSha1';

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

        if ('' !== $config['callback']) {
            $callbackMethod = $refl->getMethod($config['callback']);
            $callbackMethod->setAccessible(true);

            $callbackMethod->invokeArgs($object, [$info]);
        }

        if ($config['filePathField']) {
            $this->updateField($object, $uow, $ea, $meta, $config['filePathField'], $info['filePath']);
        }

        if ($config['fileNameField']) {
            $this->updateField($object, $uow, $ea, $meta, $config['fileNameField'], $info['fileName']);
        }

        if ($config['fileMimeTypeField']) {
            $this->updateField($object, $uow, $ea, $meta, $config['fileMimeTypeField'], $info['fileMimeType']);
        }

        if ($config['fileSizeField']) {
            $typeOfSizeField = Type::getType($meta->getTypeOfField($config['fileSizeField']));
            $value = $typeOfSizeField->convertToPHPValue(
                $info['fileSize'],
                $om->getConnection()->getDatabasePlatform()
            );
            $this->updateField($object, $uow, $ea, $meta, $config['fileSizeField'], $value);
        }

        $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);

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
     * Simple wrapper for the function "unlink" to ease testing
     *
     * @param string $filePath
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
     * @param string      $path
     * @param string|bool $filenameGeneratorClass
     * @param bool        $overwrite
     * @param bool        $appendNumber
     * @param object      $object
     *
     * @throws UploadableUploadException
     * @throws UploadableNoFileException
     * @throws UploadableExtensionException
     * @throws UploadableIniSizeException
     * @throws UploadableFormSizeException
     * @throws UploadableFileAlreadyExistsException
     * @throws UploadablePartialException
     * @throws UploadableNoTmpDirException
     * @throws UploadableCantWriteException
     *
     * @return array<string, int|string|null>
     *
     * @phpstan-param class-string|false $filenameGeneratorClass
     */
    public function moveFile(FileInfoInterface $fileInfo, $path, $filenameGeneratorClass = false, $overwrite = false, $appendNumber = false, $object = null)
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

                    throw new UploadableNoFileException($msg);
                case 6:
                    $msg = 'Upload failed. Temp dir is missing.';

                    throw new UploadableNoTmpDirException($msg);
                case 7:
                    $msg = 'File "%s" couldn\'t be uploaded because directory is not writable.';

                    throw new UploadableCantWriteException(sprintf($msg, $fileInfo->getName()));
                case 8:
                    $msg = 'A PHP Extension stopped the uploaded for some reason.';

                    throw new UploadableExtensionException($msg);
                default:
                    throw new UploadableUploadException(sprintf('There was an unknown problem while uploading file "%s"', $fileInfo->getName()));
            }
        }

        $info = [
            'fileName' => '',
            'fileExtension' => '',
            'fileWithoutExt' => '',
            'origFileName' => '',
            'filePath' => '',
            'fileMimeType' => $fileInfo->getType(),
            'fileSize' => $fileInfo->getSize(),
        ];

        $info['fileName'] = basename($fileInfo->getName());
        $info['filePath'] = $path.'/'.$info['fileName'];

        $hasExtension = strrpos($info['fileName'], '.');

        if ($hasExtension) {
            $info['fileExtension'] = substr($info['filePath'], strrpos($info['filePath'], '.'));
            $info['fileWithoutExt'] = substr($info['filePath'], 0, strrpos($info['filePath'], '.'));
        } else {
            $info['fileWithoutExt'] = $info['filePath'];
        }

        // Save the original filename for later use
        $info['origFileName'] = $info['fileName'];

        // Now we generate the filename using the configured class
        if (false !== $filenameGeneratorClass) {
            $filename = $filenameGeneratorClass::generate(
                str_replace($path.'/', '', $info['fileWithoutExt']),
                $info['fileExtension'],
                $object
            );
            $info['filePath'] = str_replace(
                '/'.$info['fileName'],
                '/'.$filename,
                $info['filePath']
            );
            $info['fileName'] = $filename;

            if ($pos = strrpos($info['filePath'], '.')) {
                // ignores positions like "./file" at 0 see #915
                $info['fileWithoutExt'] = substr($info['filePath'], 0, $pos);
            } else {
                $info['fileWithoutExt'] = $info['filePath'];
            }
        }

        if (is_file($info['filePath'])) {
            if ($overwrite) {
                $this->cancelFileRemoval($info['filePath']);
                $this->removeFile($info['filePath']);
            } elseif ($appendNumber) {
                $counter = 1;
                $info['filePath'] = $info['fileWithoutExt'].'-'.$counter.$info['fileExtension'];

                do {
                    $info['filePath'] = $info['fileWithoutExt'].'-'.(++$counter).$info['fileExtension'];
                } while (is_file($info['filePath']));
            } else {
                throw new UploadableFileAlreadyExistsException(sprintf('File "%s" already exists!', $info['filePath']));
            }
        }

        if (!$this->doMoveFile($fileInfo->getTmpName(), $info['filePath'], $fileInfo->isUploadedFile())) {
            throw new UploadableUploadException(sprintf('File "%s" was not uploaded, or there was a problem moving it to the location "%s".', $fileInfo->getName(), $path));
        }

        return $info;
    }

    /**
     * Simple wrapper method used to move the file. If it's an uploaded file
     * it will use the "move_uploaded_file method. If it's not, it will
     * simple move it
     *
     * @param string $source         Source file
     * @param string $dest           Destination file
     * @param bool   $isUploadedFile Whether this is an uploaded file?
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
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Sets the default path
     *
     * @param string $path
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
     * @return string|null
     */
    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    /**
     * Sets file info default class
     *
     * @param string $defaultFileInfoClass
     *
     * @return void
     */
    public function setDefaultFileInfoClass($defaultFileInfoClass)
    {
        if (!is_string($defaultFileInfoClass) || !class_exists($defaultFileInfoClass)
            || !is_subclass_of($defaultFileInfoClass, FileInfoInterface::class)
        ) {
            throw new InvalidArgumentException(sprintf('Default FileInfo class must be a valid class, and it must implement "%s".', FileInfoInterface::class));
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
     * @param object                        $entity
     * @param array|FileInfoInterface|mixed $fileInfo
     *
     * @phpstan-assert FileInfoInterface|array $fileInfo
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function addEntityFileInfo($entity, $fileInfo)
    {
        $fileInfoClass = $this->getDefaultFileInfoClass();
        $fileInfo = is_array($fileInfo) ? new $fileInfoClass($fileInfo) : $fileInfo;

        if (!$fileInfo instanceof FileInfoInterface) {
            $msg = 'You must pass an instance of FileInfoInterface or a valid array for entity of class "%s".';

            throw new \RuntimeException(sprintf($msg, get_class($entity)));
        }

        $this->fileInfoObjects[spl_object_id($entity)] = [
            'entity' => $entity,
            'fileInfo' => $fileInfo,
        ];
    }

    /**
     * @param object $entity
     *
     * @return FileInfoInterface
     */
    public function getEntityFileInfo($entity)
    {
        $oid = spl_object_id($entity);

        if (!isset($this->fileInfoObjects[$oid])) {
            throw new \RuntimeException(sprintf('There\'s no FileInfoInterface object for entity of class "%s".', get_class($entity)));
        }

        return $this->fileInfoObjects[$oid]['fileInfo'];
    }

    /**
     * @return void
     */
    public function setMimeTypeGuesser(MimeTypeGuesserInterface $mimeTypeGuesser)
    {
        $this->mimeTypeGuesser = $mimeTypeGuesser;
    }

    /**
     * @return MimeTypeGuesserInterface
     */
    public function getMimeTypeGuesser()
    {
        return $this->mimeTypeGuesser;
    }

    /**
     * @param array<string, mixed> $config
     * @param object               $object Entity
     *
     * @throws UploadableNoPathDefinedException
     *
     * @return string
     */
    protected function getPath(ClassMetadata $meta, array $config, $object)
    {
        $path = $config['path'];

        if ('' === $path) {
            $defaultPath = $this->getDefaultPath();
            if ('' !== $config['pathMethod']) {
                $getPathMethod = \Closure::bind(fn (string $pathMethod, ?string $defaultPath): string => $this->{$pathMethod}($defaultPath), $object, $meta->getReflectionClass()->getName());

                $path = $getPathMethod($config['pathMethod'], $defaultPath);
            } elseif (null !== $defaultPath) {
                $path = $defaultPath;
            } else {
                $msg = 'You have to define the path to save files either in the listener, or in the class "%s"';

                throw new UploadableNoPathDefinedException(sprintf($msg, $meta->getName()));
            }
        }

        Validator::validatePath($path);

        return rtrim($path, '\/');
    }

    /**
     * @param ClassMetadata        $meta
     * @param array<string, mixed> $config
     * @param object               $object Entity
     *
     * @return void
     */
    protected function addFileRemoval($meta, $config, $object)
    {
        if ($config['filePathField']) {
            $this->pendingFileRemovals[] = $this->getFilePathFieldValue($meta, $config, $object);
        } else {
            $path = $this->getPath($meta, $config, $object);
            $fileName = $this->getFileNameFieldValue($meta, $config, $object);
            $this->pendingFileRemovals[] = $path.DIRECTORY_SEPARATOR.$fileName;
        }
    }

    /**
     * @param string $filePath
     *
     * @return void
     */
    protected function cancelFileRemoval($filePath)
    {
        $k = array_search($filePath, $this->pendingFileRemovals, true);

        if (false !== $k) {
            unset($this->pendingFileRemovals[$k]);
        }
    }

    /**
     * Returns value of the entity's property
     *
     * @param string $propertyName
     * @param object $object
     *
     * @return mixed
     */
    protected function getPropertyValueFromObject(ClassMetadata $meta, $propertyName, $object)
    {
        $getFilePath = \Closure::bind(fn (string $propertyName) => $this->{$propertyName}, $object, $meta->getReflectionClass()->getName());

        return $getFilePath($propertyName);
    }

    /**
     * Returns the path of the entity's file
     *
     * @param array<string, mixed> $config
     * @param object               $object
     *
     * @return string
     */
    protected function getFilePathFieldValue(ClassMetadata $meta, array $config, $object)
    {
        return $this->getPropertyValueFromObject($meta, $config['filePathField'], $object);
    }

    /**
     * Returns the name of the entity's file
     *
     * @param array<string, mixed> $config
     * @param object               $object
     *
     * @return string
     */
    protected function getFileNameFieldValue(ClassMetadata $meta, array $config, $object)
    {
        return $this->getPropertyValueFromObject($meta, $config['fileNameField'], $object);
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @param object $object
     * @param object $uow
     * @param string $field
     * @param mixed  $value
     * @param bool   $notifyPropertyChanged
     *
     * @return void
     */
    protected function updateField($object, $uow, AdapterInterface $ea, ClassMetadata $meta, $field, $value, $notifyPropertyChanged = true)
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $property->setValue($object, $value);

        if ($notifyPropertyChanged && $object instanceof NotifyPropertyChanged) {
            $uow = $ea->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $value);
        }
    }
}
