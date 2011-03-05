<?php

namespace Gedmo\Loggable\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository,
    Doctrine\ODM\MongoDB\Cursor;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Document\Repository
 * @subpackage LogEntryRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LogEntryRepository extends DocumentRepository
{    
    /**
     * Loads all log entries for the
     * given $document
     * 
     * @param object $document
     * @return array
     */ 
    public function getLogEntries($document)
    {
        $objectClass = get_class($document);
        $objectMeta = $this->dm->getClassMetadata($objectClass);
        $objectId = $objectMeta->getReflectionProperty($objectMeta->identifier)->getValue($document);

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->name);
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
        $objectClass = get_class($document);
        $objectMeta = $this->dm->getClassMetadata($objectClass);
        $meta = $this->getClassMetadata();
        $objectId = $objectMeta->getReflectionProperty($objectMeta->identifier)->getValue($document);
        
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
            $fields = array();
            foreach ($objectMeta->fieldMappings as $mapping) {
                if ($objectMeta->identifier !== $mapping['fieldName'] &&
                    !$objectMeta->isCollectionValuedAssociation($mapping['fieldName'])
                ) {
                    $fields[] = $mapping['fieldName'];
                }
            }
            $filled = false;
            while (($log = array_pop($logs)) && !$filled) {
                if ($data = $log->getData()) {
                    foreach ($data as $field => $value) {
                        if (in_array($field, $fields)) {
                            if ($objectMeta->isSingleValuedAssociation($field)) {
                                $mapping = $objectMeta->getFieldMapping($field);
                                $value = $this->dm->getReference($mapping['targetDocument'], current($value));
                            }
                            $objectMeta->getReflectionProperty($field)->setValue($document, $value);
                            unset($fields[array_search($field, $fields)]);
                        }
                    }
                }
                $filled = count($fields) === 0;
            }
            if (count($fields)) {
                throw new \Gedmo\Exception\UnexpectedValueException('Cound not fully revert the document to version: '.$version);
            }
        } else {
            throw new \Gedmo\Exception\UnexpectedValueException('Count not find any log entries under version: '.$version);
        }
    }
}