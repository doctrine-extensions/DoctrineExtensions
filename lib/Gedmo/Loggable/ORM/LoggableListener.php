<?php

namespace Gedmo\Loggable\ORM;

use Gedmo\Loggable\AbstractLoggableListener;

use Doctrine\ORM\Events,
    Doctrine\Dbal\Types\Type,
    Doctrine\Common\EventArgs;

/**
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @package Gedmo.Loggable.ORM
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
    protected $defaultLoggableEntity = 'Gedmo\Loggable\Entity\HistoryLog';

    protected $logger;

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::loadClassMetadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertLogRecord($om, $log)
    {
        $meta = $om->getClassMetadata(get_class($log));
        $data = array();

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->getColumnName($fieldName)] = $reflProp->getValue($log);
            }
        }

        // DateTime value isn't converted to string
        $data['date'] = Type::getType('datetime')->convertToDatabaseValue($data['date'],
            $om->getConnection()->getDatabasePlatform()
        );

        $table = $meta->getTableName();
        if (!$om->getConnection()->insert($table, $data)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Log record');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectClass()
    {
        return $this->defaultLoggableEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getEntityManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }
}