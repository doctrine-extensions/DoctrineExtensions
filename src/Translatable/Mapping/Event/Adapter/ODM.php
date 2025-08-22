<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation;
use Gedmo\Translatable\Document\Translation;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ODM extends BaseAdapterODM implements TranslatableAdapter
{
    public function usesPersonalTranslation($translationClassName)
    {
        return $this
            ->getObjectManager()
            ->getClassMetadata($translationClassName)
            ->getReflectionClass()
            ->isSubclassOf(AbstractPersonalTranslation::class)
        ;
    }

    public function getDefaultTranslationClass()
    {
        return Translation::class;
    }

    public function loadTranslations($object, $translationClass, $locale, $objectClass)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        assert($wrapped instanceof MongoDocumentWrapper);
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

        return $q->getIterator()->toArray();
    }

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

    public function insertTranslationRecord($translation)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata(get_class($translation));
        $collection = $dm->getDocumentCollection($meta->getName());
        $data = [];

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->getFieldMapping($fieldName)['name']] = $reflProp->getValue($translation);
            }
        }

        $insertResult = $collection->insertOne($data);

        if (false === $insertResult->isAcknowledged()) {
            throw new RuntimeException('Failed to insert new Translation record');
        }
    }

    public function getTranslationValue($object, $field, $value = false)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        assert($wrapped instanceof MongoDocumentWrapper);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = $this->getType($mapping['type']);
        if (false === $value) {
            $value = $wrapped->getPropertyValue($field);
        }

        return $type->convertToDatabaseValue($value);
    }

    public function setTranslationValue($object, $field, $value)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        assert($wrapped instanceof MongoDocumentWrapper);
        $meta = $wrapped->getMetadata();
        $mapping = $meta->getFieldMapping($field);
        $type = $this->getType($mapping['type']);

        $value = $type->convertToPHPValue($value);
        $wrapped->setPropertyValue($field, $value);
    }

    private function getType(string $type): Type
    {
        return Type::getType($type);
    }
}
