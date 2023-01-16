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
     *
     * @var UploadableListener
     */
    private $uploadableListener;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @todo Check if this property must be removed, as it is not used.
     *
     * @var array
     */
    private $config = [];

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

    /**
     * @var FileInfoInterface
     */
    private $fileInfo;

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
     * @return \Gedmo\Uploadable\UploadableListener
     */
    public function getListener()
    {
        return $this->uploadableListener;
    }

    /**
     * Retrieve associated EntityManager
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager()
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
     * @return \Gedmo\Uploadable\FileInfo\FileInfoInterface
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
