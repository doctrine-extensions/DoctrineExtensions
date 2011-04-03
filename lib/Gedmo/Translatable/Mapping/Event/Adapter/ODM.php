<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Cursor;

/**
 * Doctrine event adapter for ODM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Translatable\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM
{
    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    public function getDefaultTranslationClass()
    {
        return 'Gedmo\\Translatable\\Document\\Translation';
    }

    /**
     * Load the translations for a given object
     *
     * @param object $object
     * @param string $translationClass
     * @param string $locale
     * @return array
     */
    public function loadTranslations($object, $translationClass, $locale)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata(get_class($object));

        // load translated content for all translatable fields
        $identifier = $this->extractIdentifier($dm, $object);
        // construct query
        $qb = $dm->createQueryBuilder($translationClass);
        $q = $qb->field('foreignKey')->equals($identifier)
            ->field('locale')->equals($locale)
            ->field('objectClass')->equals($meta->name)
            ->getQuery();

        $q->setHydrate(false);
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * Search for existing translation record
     *
     * @param mixed $objectId
     * @param string $objectClass
     * @param string $locale
     * @param string $field
     * @param string $translationClass
     * @return mixed - null if nothing is found, Translation otherwise
     */
    public function findTranslation($objectId, $objectClass, $locale, $field, $translationClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm->createQueryBuilder($translationClass);
        $q = $qb->field('foreignKey')->equals($objectId)
            ->field('locale')->equals($locale)
            ->field('field')->equals($field)
            ->field('objectClass')->equals($objectClass)
            ->getQuery();

        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = current($result->toArray());
        }
        $q->setHydrate(false);
        return $result;
    }

    /**
     * Removes all associated translations for given object
     *
     * @param mixed $objectId
     * @param string $transClass
     * @return void
     */
    public function removeAssociatedTranslations($objectId, $transClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm->createQueryBuilder($transClass);
        $q = $qb->remove()
            ->field('foreignKey')->equals($objectId)
            ->getQuery();
        return $q->execute();
    }

    /**
     * Inserts the translation record
     *
     * @param object $translation
     * @return void
     */
    public function insertTranslationRecord($translation)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata(get_class($translation));
        $collection = $dm->getDocumentCollection($meta->name);
        $data = array();

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->fieldMappings[$fieldName]['name']] = $reflProp->getValue($translation);
            }
        }

        if (!$collection->insert($data)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Translation record');
        }
    }
}