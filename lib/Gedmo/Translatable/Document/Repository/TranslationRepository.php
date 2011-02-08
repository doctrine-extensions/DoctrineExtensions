<?php

namespace Gedmo\Translatable\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository,
    Doctrine\ODM\MongoDB\Cursor;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Document.Repository
 * @subpackage TranslationRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationRepository extends DocumentRepository
{    
    /**
     * Loads all translations with all translatable
     * fields from the given entity
     * 
     * @param object $document
     * @return array list of translations in locale groups
     */
    public function findTranslations($document)
    {
        $result = array();
        if ($document) {
            $meta = $this->dm->getClassMetadata(get_class($document));
            $identifier = $meta->identifier;
            $documentId = $meta->getReflectionProperty($identifier)->getValue($document);
            
            $translationMeta = $this->getClassMetadata();
            $qb = $this->createQueryBuilder();
            $q = $qb->field('foreignKey')->equals($documentId)
                ->field('objectClass')->equals($meta->name)
                ->sort('locale', 'asc')
                ->getQuery();
            
            $q->setHydrate(false);
            $data = $q->execute();
            if ($data instanceof Cursor) {
                $data = $data->toArray();
            }            
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }
        return $result;
    }
    
    /**
     * Find the object $class by the translated field.
     * Result is the first occurence of translated field.
     * Query can be slow, since there are no indexes on such
     * columns
     * 
     * @param string $field
     * @param string $value
     * @param string $class
     * @return object - instance of $class or null if not found
     */
    public function findObjectByTranslatedField($field, $value, $class)
    {
        $document = null;
        $meta = $this->dm->getClassMetadata($class);
        $translationMeta = $this->getClassMetadata();
        if ($meta->hasField($field)) {
            $qb = $this->createQueryBuilder();
            $q = $qb->field('field')->equals($field)
                ->field('objectClass')->equals($meta->name)
                ->field('content')->equals($value)
                ->getQuery();
            
            $q->setHydrate(false);
            $result = $q->execute();
            if ($result instanceof Cursor) {
                $result = $data->toArray();
            }
            $id = count($result) ? $result[0]['foreignKey'] : null;
            if ($id) {
                $document = $this->dm->find($class, $id);
            }
        }
        return $entity;
    }
    
    /**
     * Loads all translations with all translatable
     * fields by a given document primary key
     *
     * @param mixed $id - primary key value of document
     * @return array
     */
    public function findTranslationsByObjectId($id)
    {
        $result = array();
        if ($id) {
            $translationMeta = $this->getClassMetadata();
            $qb = $this->createQueryBuilder();
            $q = $qb->field('foreignKey')->equals($id)
                ->sort('locale', 'asc')
                ->getQuery();
            
            $q->setHydrate(false);
            $data = $q->execute();
                       
            if ($data instanceof Cursor) {
                $data = $data->toArray();
            }            
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }
        return $result;
    }
}