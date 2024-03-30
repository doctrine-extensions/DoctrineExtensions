<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Deprecations\Deprecation;
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
     * The configuration of the Uploadable extension for this entity class
     *
     * @todo Check if this property must be removed, as it is never set.
     *
     * @var array
     */
    private $extensionConfiguration;

    /**
     * @param object $entity
     * @param string $action
     */
    public function __construct(
        /**
         * The instance of the Uploadable listener that fired this event
         */
        private readonly UploadableListener $uploadableListener,
        private readonly EntityManagerInterface $em,
        /**
         * @todo Check if this property must be removed, as it is not used.
         *
         * @var array<mixed, mixed>
         */
        private readonly array $config,
        private readonly FileInfoInterface $fileInfo,
        /**
         * The Uploadable entity
         */
        private $entity,
        /**
         * Is the file being created, updated or removed?
         * This value can be: CREATE, UPDATE or DELETE
         */
        private $action
    ) {
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
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            '"%s()" is deprecated since gedmo/doctrine-extensions 3.14 and will be removed in version 4.0.',
            __METHOD__
        );

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
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            '"%s()" is deprecated since gedmo/doctrine-extensions 3.14 and will be removed in version 4.0.',
            __METHOD__
        );

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

    /** @return array<mixed, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }
}
