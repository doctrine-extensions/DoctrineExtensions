<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;
use Doctrine\ODM\MongoDB\Mapping\Types\Type;

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
final class ODM extends BaseAdapterODM implements TranslatableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultTranslationClass()
    {
        return 'Gedmo\\Translatable\\Document\\Translation';
    }

    /**
     * {@inheritDoc}
     */
    public function loadTranslations($object, $translationClass, $locale)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrapp($object, $dm);

        // load translated content for all translatable fields
        $identifier = $wrapped->getIdentifier();
        // construct query
        $qb = $dm->createQueryBuilder($translationClass);
        $q = $qb->field('foreignKey')->equals($identifier)
            ->field('locale')->equals($locale)
            ->field('objectClass')->equals($wrapped->getMetadata()->name)
            ->getQuery();

        $q->setHydrate(false);
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function removeAssociatedTranslations($objectId, $transClass, $targetClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm->createQueryBuilder($transClass);
        $q = $qb->remove()
            ->field('foreignKey')->equals($objectId)
            ->field('objectClass')->equals($targetClass)
            ->getQuery()
        ;
        return $q->execute();
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function getTranslationValue($object, $field, $value = false)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrapp($object, $dm);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = Type::getType($mapping['type']);
        if ($value === false) {
            $value = $wrapped->getPropertyValue($field);
        }
        return $type->convertToDatabaseValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslationValue($object, $field, $value)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrapp($object, $dm);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = Type::getType($mapping['type']);

        $value = $type->convertToPHPValue($value);
        $wrapped->setPropertyValue($field, $value);
    }
}