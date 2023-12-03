<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Uploadable\FileInfo\FileInfoInterface;
use Gedmo\Uploadable\UploadableListener;

/**
 * Abstract Base Event to be extended by Uploadable events
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class UploadableBaseEventArgs extends EventArgs
{
    /**
     * The instance of the Uploadable listener that fired this event
     */
    private UploadableListener $uploadableListener;

    private EntityManagerInterface $em;

    /**
     * @todo Check if this property must be removed, as it is not used.
     */
    private array $config = [];

    /**
     * The Uploadable entity
     *
     * @var object
     */
    private $entity;

    /**
     * The configuration of the Uploadable extension for this entity class
     *
     * @todo Check if this property must be removed, as it is never set.
     *
     * @var array
     */
    private $extensionConfiguration;

    private FileInfoInterface $fileInfo;

    /**
     * Is the file being created, updated or removed?
     * This value can be: CREATE, UPDATE or DELETE
     *
     * @var string
     */
    private $action;

    /**
     * @param object $entity
     * @param string $action
     */
    public function __construct(UploadableListener $listener, EntityManagerInterface $em, array $config, FileInfoInterface $fileInfo, $entity, $action)
    {
        $this->uploadableListener = $listener;
        $this->em = $em;
        $this->config = $config;
        $this->fileInfo = $fileInfo;
        $this->entity = $entity;
        $this->action = $action;
    }

    /**
     * Retrieve the associated listener
     *
     * @return UploadableListener
     */
    public function getListener()
    {
        return $this->uploadableListener;
    }

    /**
     * Retrieve associated EntityManager
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        @trigger_error(sprintf(
            '"%s()" is deprecated since gedmo/doctrine-extensions 3.14 and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        return $this->em;
    }

    /**
     * Retrieve associated EntityManager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->em;
    }

    /**
     * Retrieve associated Entity
     *
     * @return object
     */
    public function getEntity()
    {
        @trigger_error(sprintf(
            '"%s()" is deprecated since gedmo/doctrine-extensions 3.14 and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        return $this->entity;
    }

    /**
     * Retrieve associated Object
     *
     * @return object
     */
    public function getObject()
    {
        return $this->entity;
    }

    /**
     * Retrieve associated Uploadable extension configuration
     *
     * @return array
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * Retrieve the FileInfo associated with this entity.
     *
     * @return FileInfoInterface
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    /**
     * Retrieve the action being performed to the entity: CREATE, UPDATE or DELETE
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
