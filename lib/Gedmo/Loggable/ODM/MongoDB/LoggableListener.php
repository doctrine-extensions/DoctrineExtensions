<?php

namespace Gedmo\Loggable\ODM\MongoDB;

use Gedmo\Loggable\AbstractLoggableListener,
    Doctrine\Common\EventArgs,
    Doctrine\ODM\MongoDB\Events;

/**
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.ODM.MongoDB
 * @subpackage LoggableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableListener extends AbstractLoggableListener
{
    /**
     * The loggable document class used to store the logs
     *
     * @var string
     */
    protected $defaultLoggableDocument = 'Gedmo\Loggable\Document\HistoryLog';

    protected $logger;

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postPersist, Events::loadClassMetadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertLogRecord($om, $log)
    {
        $meta = $om->getClassMetadata(get_class($log));
        $collection = $om->getDocumentCollection($meta->name);
        $data = array();

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->fieldMappings[$fieldName]['name']] = $reflProp->getValue($log);
            }
        }

        if (!$collection->insert($data)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Log record');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getLogClass()
    {
        return $this->defaultLoggableDocument;
    }

    /**
     * {@inheritdoc}
     */
    protected function getObject(EventArgs $args)
    {
        return $args->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getDocumentManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName($meta)
    {
        return $meta->identifier;
    }
}