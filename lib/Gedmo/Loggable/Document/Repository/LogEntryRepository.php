<?php

namespace Gedmo\Loggable\Document\Repository;

use Gedmo\Loggable\LoggableListener;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Exception\RuntimeException;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LogEntryRepository extends DocumentRepository
{
    /**
     * Currently used loggable listener
     *
     * @var LoggableListener
     */
    private $listener;

    /**
     * Loads all log entries for the
     * given $document
     *
     * @param object $document
     * @return array
     */
    public function getLogEntries($document)
    {
        if (!$this->dm->getUnitOfWork()->isInIdentityMap($document)) {
            throw new InvalidArgumentException("Document is not managed by UnitOfWork");
        }
        $this->dm->initializeObject($document);

        $objectId = OMH::getIdentifier($this->dm, $document);
        $meta = $this->dm->getClassMetadata(get_class($document));

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals(OMH::getRootObjectClass($meta));
        $qb->sort('version', 'DESC');
        $q = $qb->getQuery();

        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * Reverts given $document to $revision by
     * restoring all fields from that $revision.
     * After this operation you will need to
     * persist and flush the $document.
     *
     * @param object $document
     * @param integer $version
     * @throws \Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function revert($document, $version = 1)
    {
        if (!$this->dm->getUnitOfWork()->isInIdentityMap($document)) {
            throw new InvalidArgumentException("Document is not managed by UnitOfWork");
        }
        $this->dm->initializeObject($document);

        $objectId = OMH::getIdentifier($this->dm, $document);
        $objectMeta = $this->dm->getClassMetadata(get_class($document));
        $meta = $this->getClassMetadata();

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals(OMH::getRootObjectClass($objectMeta));
        $qb->field('version')->lte($version);
        $qb->sort('version', 'ASC');
        $q = $qb->getQuery();

        $logs = $q->execute();
        if ($logs instanceof Cursor) {
            $logs = $logs->toArray();
        }
        if ($logs) {
            $exm = $this->getLoggableListener()->getConfiguration($this->dm, $objectMeta->name);
            $fields = $exm->getVersionedFields();
            $filled = false;
            while (($log = array_pop($logs)) && !$filled) {
                if ($data = $log->getData()) {
                    foreach ($data as $field => $value) {
                        if (in_array($field, $fields)) {
                            if ($objectMeta->isSingleValuedAssociation($field)) {
                                $mapping = $objectMeta->getFieldMapping($field);
                                $value = $value ? $this->dm->getReference($mapping['targetDocument'], $value) : null;
                            }
                            $objectMeta->getReflectionProperty($field)->setValue($document, $value);
                            unset($fields[array_search($field, $fields)]);
                        }
                    }
                }
                $filled = count($fields) === 0;
            }
            /*if (count($fields)) {
                throw new \Gedmo\Exception\UnexpectedValueException('Cound not fully revert the document to version: '.$version);
            }*/
        } else {
            throw new UnexpectedValueException('Count not find any log entries under version: '.$version);
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
            foreach ($this->dm->getEventManager()->getListeners() as $event => $listeners) {
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
