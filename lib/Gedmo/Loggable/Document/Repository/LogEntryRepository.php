<?php

namespace Gedmo\Loggable\Document\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Gedmo\Loggable\LoggableListener;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Cursor;

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
     *
     * @return array
     */
    public function getLogEntries($document)
    {
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        $objectId = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($wrapped->getMetadata()->name);
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
     * @param object  $document
     * @param integer $version
     *
     * @throws \Gedmo\Exception\UnexpectedValueException
     *
     * @return void
     */
    public function revert($document, $version = 1)
    {
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        $objectMeta = $wrapped->getMetadata();
        $objectId = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->name);
        $qb->field('version')->lte($version);
        $qb->sort('version', 'ASC');
        $q = $qb->getQuery();

        $logs = $q->execute();
        if ($logs instanceof Cursor) {
            $logs = $logs->toArray();
        }
        if ($logs) {
            $config = $this->getLoggableListener()->getConfiguration($this->dm, $objectMeta->name);
            $fields = $config['versioned'];
            $embedFields = $this->resolveFieldsForEmbedDocument($fields, $objectMeta);
            $filled = false;
            $completeResolved = false;
            while (($log = array_pop($logs)) && !$filled) {
                if ($data = $log->getData()) {
                    if (!$completeResolved) {
                        list($embedFields, $fields, $completeResolved)  = $this->resolveFieldsForManyEmbedDocument($embedFields, $objectMeta, $data, $fields);
                    }

                    list($fields, $embedFields) = $this->restoreData(
                        $data,
                        $fields,
                        $objectMeta,
                        $wrapped,
                        $embedFields
                    );
                }
                $filled = count($fields) === 0;
            }
            /*if (count($fields)) {
                throw new \Gedmo\Exception\UnexpectedValueException('Could not fully revert the document to version: '.$version);
            }*/
        } else {
            throw new \Gedmo\Exception\UnexpectedValueException('Could not find any log entries under version: '.$version);
        }
    }

    /**
     * @param array         $fields
     * @param ClassMetadata $meta
     * @return array
     */
    private function resolveFieldsForEmbedDocument(array $fields, $meta)
    {
        $embedFields = array();
        foreach ($fields as $field) {
            if ($meta->isCollectionValuedEmbed($field)) {
                $config              = $this->getLoggableListener()->getConfiguration(
                    $this->dm,
                    $meta->getAssociationTargetClass($field)
                );
                $embedFields[$field] = $config['versioned'];
            }
            if ($meta->isSingleValuedEmbed($field)) {
                $config              = $this->getLoggableListener()->getConfiguration(
                    $this->dm,
                    $meta->getAssociationTargetClass($field)
                );
                $embedFields[$field] = $config['versioned'];
            }
        }

        return $embedFields;
    }

    /**
     * @param array         $embedFields
     * @param ClassMetadata $meta
     * @param array         $data
     * @param array         $fields
     * @return array
     */
    private function resolveFieldsForManyEmbedDocument(array $embedFields, $meta, $data, array $fields)
    {
        $completeResolved = true;
        foreach ($embedFields as $field => $fieldData) {
            if ($meta->isCollectionValuedEmbed($field) && isset($data[$field])) {
                $embedFields[$field] = array();
                for ($i = 0; $i < count($data[$field]); $i++) {
                    $embedFields[$field][$i] = $fieldData;
                }
                continue;
            }

            if ($meta->isCollectionValuedEmbed($field)) {
                $completeResolved = false;
            }
        }

        return array($embedFields, $fields, $completeResolved);
    }

    /**
     * Get the currently used LoggableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     *
     * @return LoggableListener
     */
    private function getLoggableListener()
    {
        if (is_null($this->listener)) {
            foreach ($this->dm->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof LoggableListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The loggable listener could not be found');
            }
        }

        return $this->listener;
    }

    /**
     * @param array                $data
     * @param array                $fields
     * @param ClassMetadata        $objectMeta
     * @param MongoDocumentWrapper $wrapped
     * @param array                $embedFields
     * @return array
     */
    protected function restoreData($data, $fields, $objectMeta, $wrapped, $embedFields = array())
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $fields)) {
                if ($objectMeta->isCollectionValuedEmbed($field)) {

                    $objectCollection = $wrapped->getPropertyValue($field);

                    if (!isset($objectCollection)) {
                        $objectCollection = array();
                    }
                    if ($objectCollection instanceof Collection) {
                        $objectCollection = $objectCollection->toArray();
                    }

                    foreach ($value as $key => $valueData) {

                        if (!isset($embedFields[$field][$key])) {
                            continue;
                        }

                        if (!isset($objectCollection[$key])) {
                            $class  = $objectMeta->getAssociationTargetClass($field);
                            $object = new $class;
                            $objectCollection[$key] = $object;
                        } else {
                            $object = $objectCollection[$key];
                        }

                        $wrappedEmbed    = new MongoDocumentWrapper($object, $this->dm);
                        $objectEmbedMeta = $wrappedEmbed->getMetadata();

                        list($embedSubFields) = $this->restoreData(
                            $value[$key],
                            $embedFields[$field][$key],
                            $objectEmbedMeta,
                            $wrappedEmbed,
                            $embedFields
                        );
                        $embedFields[$field][$key] = $embedSubFields;
                        if (count($embedFields[$field][$key]) === 0) {
                            unset($embedFields[$field][$key]);
                        }
                    }

                    for (count($objectCollection); count($objectCollection) > count($value);  ) {
                        unset($objectCollection[count($objectCollection)-1]);
                    }

                    $wrapped->setPropertyValue($field, $objectCollection);
                    if (count($embedFields[$field]) === 0) {
                        unset($fields[array_search($field, $fields)]);
                        unset($embedFields[$field]);
                    }
                    continue;
                }
                if ($objectMeta->isSingleValuedEmbed($field)) {
                    if (!isset($value)) {
                        unset($fields[array_search($field, $fields)]);
                        unset($embedFields[$field]);
                        $wrapped->setPropertyValue($field, $value);
                        continue;
                    }
                    $object = $wrapped->getPropertyValue($field);
                    if (!isset($object)) {
                        $class  = $objectMeta->getAssociationTargetClass($field);
                        $object = new $class;
                    }
                    $wrappedEmbed    = new MongoDocumentWrapper($object, $this->dm);
                    $objectEmbedMeta = $wrappedEmbed->getMetadata();

                    list($embedSubFields) = $this->restoreData(
                        $value,
                        $embedFields[$field],
                        $objectEmbedMeta,
                        $wrappedEmbed,
                        $embedFields
                    );

                    $embedFields[$field] = $embedSubFields;
                    $wrapped->setPropertyValue($field, $object);
                    if (count($embedFields[$field]) === 0) {
                        unset($fields[array_search($field, $fields)]);
                        unset($embedFields[$field]);
                    }
                    continue;
                }
                if ($objectMeta->isSingleValuedAssociation($field)) {
                    $mapping = $objectMeta->getFieldMapping($field);
                    $value   = $value ? $this->dm->getReference($mapping['targetDocument'], $value) : null;
                }
                $wrapped->setPropertyValue($field, $value);
                unset($fields[array_search($field, $fields)]);
            }
        }

        return array($fields, $embedFields);
    }
}
