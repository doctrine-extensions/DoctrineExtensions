<?php

namespace Gedmo\Loggable\Entity\Repository;

use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\ORM\EntityRepository;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\InvalidArgumentException;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LogEntryRepository extends EntityRepository
{
    /**
     * Currently used loggable listener
     *
     * @var LoggableListener
     */
    private $listener;

    /**
     * Loads all log entries for the
     * given $entity
     *
     * @param object $entity
     * @return array
     */
    public function getLogEntries($entity)
    {
        $q = $this->getLogEntriesQuery($entity);
        return $q->getResult();
    }

    /**
     * Get the query for loading of log entries
     *
     * @param object $entity
     * @return Query
     */
    public function getLogEntriesQuery($entity)
    {
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($entity)) {
            throw new InvalidArgumentException("Entity is not managed by UnitOfWork");
        }
        $this->_em->initializeObject($entity);

        $objectClass = OMH::getRootObjectClass($this->_em->getClassMetadata(get_class($entity)));
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->name} log";
        $dql .= " WHERE log.objectId = :objectId";
        $dql .= " AND log.objectClass = :objectClass";
        $dql .= " ORDER BY log.version DESC";

        $objectId = OMH::getIdentifier($this->_em, $entity);
        $q = $this->_em->createQuery($dql);
        $q->setParameters(compact('objectId', 'objectClass', 'order'));
        return $q;
    }

    /**
     * Reverts given $entity to $revision by
     * restoring all fields from that $revision.
     * After this operation you will need to
     * persist and flush the $entity.
     *
     * @param object $entity
     * @param integer $version
     * @throws \Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function revert($entity, $version = 1)
    {
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($entity)) {
            throw new InvalidArgumentException("Entity is not managed by UnitOfWork");
        }
        $this->_em->initializeObject($entity);

        $objectMeta = $this->_em->getClassMetadata(get_class($entity));
        $objectClass = OMH::getRootObjectClass($objectMeta);
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->name} log";
        $dql .= " WHERE log.objectId = :objectId";
        $dql .= " AND log.objectClass = :objectClass";
        $dql .= " AND log.version <= :version";
        $dql .= " ORDER BY log.version ASC";

        $objectId = OMH::getIdentifier($this->_em, $entity);
        $q = $this->_em->createQuery($dql);
        $q->setParameters(compact('objectId', 'objectClass', 'version'));
        $logs = $q->getResult();

        if ($logs) {
            $exm = $this->getLoggableListener()->getConfiguration($this->_em, $objectMeta->name);
            $fields = $exm->getVersionedFields();
            $filled = false;
            while (($log = array_pop($logs)) && !$filled) {
                if ($data = $log->getData()) {
                    foreach ($data as $field => $value) {
                        if (in_array($field, $fields)) {
                            if ($objectMeta->isSingleValuedAssociation($field)) {
                                $mapping = $objectMeta->getAssociationMapping($field);
                                $value = $value ? $this->_em->getReference($mapping['targetEntity'], $value) : null;
                            }
                            $objectMeta->getReflectionProperty($field)->setValue($entity, $value);
                            unset($fields[array_search($field, $fields)]);
                        }
                    }
                }
                $filled = count($fields) === 0;
            }
            /*if (count($fields)) {
                throw new \Gedmo\Exception\UnexpectedValueException('Could not fully revert the entity to version: '.$version);
            }*/
        } else {
            throw new UnexpectedValueException('Could not find any log entries under version: '.$version);
        }
    }

    /**
     * Get the currently used LoggableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return LoggableListener
     */
    private function getLoggableListener()
    {
        if (is_null($this->listener)) {
            foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof LoggableListener) {
                        return $this->listener = $listener;
                    }
                }
            }

            throw new RuntimeException('The loggable listener could not be found');
        }
        return $this->listener;
    }
}
