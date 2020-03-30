<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Doctrine\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Tool\Wrapper\AbstractWrapper;
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDefaultTranslationClass()
    {
        return 'Gedmo\\Translatable\\Document\\Translation';
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslations($object, $translationClass, $locale, $objectClass)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $result = [];

        if ($this->usesPersonalTranslation($translationClass)) {
            // first try to load it using collection
            foreach ($wrapped->getMetadata()->fieldMappings as $mapping) {
                $isRightCollection = isset($mapping['association'])
                    && ClassMetadata::REFERENCE_MANY === $mapping['association']
                    && $mapping['targetDocument'] === $translationClass
                    && 'object' === $mapping['mappedBy']
                ;
                if ($isRightCollection) {
                    $collection = $wrapped->getPropertyValue($mapping['fieldName']);
                    foreach ($collection as $trans) {
                        if ($trans->getLocale() === $locale) {
                            $result[] = [
                                'field' => $trans->getField(),
                                'content' => $trans->getContent(),
                            ];
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
     * {@inheritdoc}
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

        return $q->getSingleResult();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function insertTranslationRecord($translation)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata(get_class($translation));
        $collection = $dm->getDocumentCollection($meta->name);
        $data = [];

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->fieldMappings[$fieldName]['name']] = $reflProp->getValue($translation);
            }
        }

        $insertResult = $collection->insertOne($data);

        if (false === $insertResult->isAcknowledged()) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Translation record');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationValue($object, $field, $value = false)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = $this->getType($mapping['type']);
        if (false === $value) {
            $value = $wrapped->getPropertyValue($field);
        }

        return $type->convertToDatabaseValue($value);
    }

    /**
     * {@inheritdoc}
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
