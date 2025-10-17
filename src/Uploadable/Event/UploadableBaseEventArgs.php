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
 *
 * @phpstan-import-type UploadableConfiguration from UploadableListener
 */
abstract class UploadableBaseEventArgs extends EventArgs
{
    /**
     * @param UploadableListener         $uploadableListener     The instance of the Uploadable listener that fired this event
     * @param array<string, mixed>       $extensionConfiguration
     * @param object                     $entity                 The Uploadable entity
     * @param 'INSERT'|'UPDATE'|'DELETE' $action
     *
     * @phpstan-param UploadableConfiguration $extensionConfiguration
     */
    public function __construct(
        private readonly UploadableListener $uploadableListener,
        private readonly EntityManagerInterface $em,
        private readonly array $extensionConfiguration,
        private readonly FileInfoInterface $fileInfo,
        private $entity,
        private $action
    ) {}

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
     * @return array<string, mixed>
     *
     * @phpstan-return UploadableConfiguration
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
     * Retrieve the action being performed to the object
     *
     * @return 'INSERT'|'UPDATE'|'DELETE'
     */
    public function getAction()
    {
        return $this->action;
    }
}
