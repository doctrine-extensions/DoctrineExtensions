<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements TranslatableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function usesPersonalTranslation($translationClassName)
    {
        return $this
            ->getObjectManager()
            ->getClassMetadata($translationClassName)
            ->getReflectionClass()
            ->isSubclassOf('Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation')
        ;
    }

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
    public function loadTranslations($object, $translationClass, $locale, $objectClass)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $result = array();

        if ($this->usesPersonalTranslation($translationClass)) {
            // first try to load it using collection
            foreach ($wrapped->getMetadata()->fieldMappings as $mapping) {
                $isRightCollection = isset($mapping['association'])
                    && $mapping['association'] === ClassMetadataInfo::REFERENCE_MANY
                    && $mapping['targetDocument'] === $translationClass
                    && $mapping['mappedBy'] === 'object'
                ;
                if ($isRightCollection) {
                    $collection = $wrapped->getPropertyValue($mapping['fieldName']);
                    foreach ($collection as $trans) {
                        if ($trans->getLocale() === $locale) {
                            $result[] = array(
                                'field' => $trans->getField(),
                                'content' => $trans->getContent(),
                            );
                        }
                    }

                    return $result;
                }
            }
            $q = $dm
                ->createQueryBuilder($translationClass)
                ->field('object.$id')->equals($wrapped->getIdentifier())
                ->field('locale')->equals($locale)
                ->getQuery()
            ;
        } else {
            // load translated content for all translatable fields
            // construct query
            $q = $dm
                ->createQueryBuilder($translationClass)
                ->field('foreignKey')->equals($wrapped->getIdentifier())
                ->field('locale')->equals($locale)
                ->field('objectClass')->equals($objectClass)
                ->getQuery()
            ;
        }
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
    public function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass, $objectClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm
            ->createQueryBuilder($translationClass)
            ->field('locale')->equals($locale)
            ->field('field')->equals($field)
            ->limit(1)
        ;
        if ($this->usesPersonalTranslation($translationClass)) {
            $qb->field('object.$id')->equals($wrapped->getIdentifier());
        } else {
            $qb->field('foreignKey')->equals($wrapped->getIdentifier());
            $qb->field('objectClass')->equals($objectClass);
        }
        $q = $qb->getQuery();
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = current($result->toArray());
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass, $objectClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm
            ->createQueryBuilder($transClass)
            ->remove()
        ;
        if ($this->usesPersonalTranslation($transClass)) {
            $qb->field('object.$id')->equals($wrapped->getIdentifier());
        } else {
            $qb->field('foreignKey')->equals($wrapped->getIdentifier());
            $qb->field('objectClass')->equals($objectClass);
        }
        $q = $qb->getQuery();

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
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = $this->getType($mapping['type']);
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
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = $this->getType($mapping['type']);

        $value = $type->convertToPHPValue($value);
        $wrapped->setPropertyValue($field, $value);
    }

    private function getType($type)
    {
        // due to change in ODM beta 9
        return class_exists('Doctrine\ODM\MongoDB\Types\Type') ? \Doctrine\ODM\MongoDB\Types\Type::getType($type)
            : \Doctrine\ODM\MongoDB\Mapping\Types\Type::getType($type);
    }
}
