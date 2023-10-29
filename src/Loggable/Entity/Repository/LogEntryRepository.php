<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\Loggable;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-template T of Loggable|object
 *
 * @phpstan-extends EntityRepository<AbstractLogEntry<T>>
 */
class LogEntryRepository extends EntityRepository
{
    /**
     * Currently used loggable listener
     *
     * @phpstan-var LoggableListener<T>|null
     */
    private ?LoggableListener $listener = null;

    /**
     * Loads all log entries for the given entity
     *
     * @param object $entity
     *
     * @return AbstractLogEntry[]
     *
     * @phpstan-param T $entity
     *
     * @phpstan-return array<array-key, AbstractLogEntry<T>>
     */
    public function getLogEntries($entity)
    {
        return $this->getLogEntriesQuery($entity)->getResult();
    }

    /**
     * Get the query for loading of log entries
     *
     * @param object $entity
     *
     * @return Query
     *
     * @phpstan-param T $entity
     */
    public function getLogEntriesQuery($entity)
    {
        $wrapped = new EntityWrapper($entity, $this->getEntityManager());
        $objectClass = $wrapped->getMetadata()->getName();
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->getName()} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';
        $dql .= ' ORDER BY log.version DESC';

        $objectId = (string) $wrapped->getIdentifier(false, true);
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters([
            'objectId' => $objectId,
            'objectClass' => $objectClass,
        ]);

        return $q;
    }

    /**
     * Reverts given $entity to $revision by
     * restoring all fields from that $revision.
     * After this operation you will need to
     * persist and flush the $entity.
     *
     * @param object $entity
     * @param int    $version
     *
     * @throws UnexpectedValueException
     *
     * @return void
     *
     * @phpstan-param T $entity
     */
    public function revert($entity, $version = 1)
    {
        $wrapped = new EntityWrapper($entity, $this->getEntityManager());
        $objectMeta = $wrapped->getMetadata();
        $objectClass = $objectMeta->getName();
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->getName()} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';
        $dql .= ' AND log.version <= :version';
        $dql .= ' ORDER BY log.version DESC';

        $objectId = (string) $wrapped->getIdentifier(false, true);
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters([
            'objectId' => $objectId,
            'objectClass' => $objectClass,
            'version' => $version,
        ]);

        $config = $this->getLoggableListener()->getConfiguration($this->getEntityManager(), $objectMeta->getName());
        $fields = $config['versioned'];
        $filled = false;
        $logsFound = false;

        $logs = $q->toIterable();
        assert($logs instanceof \Generator);

        while ((null !== $log = $logs->current()) && !$filled) {
            $logsFound = true;
            $logs->next();
            if ($data = $log->getData()) {
                foreach ($data as $field => $value) {
                    if (in_array($field, $fields, true)) {
                        $this->mapValue($objectMeta, $field, $value);
                        $wrapped->setPropertyValue($field, $value);
                        unset($fields[array_search($field, $fields, true)]);
                    }
                }
            }
            $filled = [] === $fields;
        }

        if (!$logsFound) {
            throw new UnexpectedValueException('Could not find any log entries under version: '.$version);
        }

        /*if (count($fields)) {
            throw new \Gedmo\Exception\UnexpectedValueException('Could not fully revert the entity to version: '.$version);
        }*/
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     *
     * @phpstan-param ClassMetadata<T> $objectMeta
     */
    protected function mapValue(ClassMetadata $objectMeta, $field, &$value)
    {
        if (!$objectMeta->isSingleValuedAssociation($field)) {
            return;
        }

        $mapping = $objectMeta->getAssociationMapping($field);
        $value = $value ? $this->getEntityManager()->getReference($mapping['targetEntity'], $value) : null;
    }

    /**
     * Get the currently used LoggableListener
     *
     * @throws RuntimeException if listener is not found
     *
     * @phpstan-return LoggableListener<T>
     */
    private function getLoggableListener(): LoggableListener
    {
        if (null === $this->listener) {
            foreach ($this->getEntityManager()->getEventManager()->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof LoggableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new RuntimeException('The loggable listener could not be found');
            }
        }

        return $this->listener;
    }
}
