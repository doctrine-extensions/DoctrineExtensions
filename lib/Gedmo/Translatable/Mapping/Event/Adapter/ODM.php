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
    public function usesPersonalTranslation($translationClassName)
    {
        return $this
            ->getObjectManager()
            ->getClassMetadata($translationClassName)
            ->getReflectionClass()
            ->isSubclassOf('Gedmo\Translatable\Document\AbstractPersonalTranslation')
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
    public function loadTranslations($object, $translationClass, $locale)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrapp($object, $dm);
        $result = array();

        if ($this->usesPersonalTranslation($translationClass)) {
            // first try to load it using collection
            die(print_r($wrapped->getMetadata()));
            $found = false;
            foreach ($wrapped->getMetadata()->associationMappings as $assoc) {
                $isRightCollection = $assoc['targetEntity'] === $translationClass
                && $assoc['mappedBy'] === 'object'
                && $assoc['type'] === ClassMetadataInfo::ONE_TO_MANY
                ;
                if ($isRightCollection) {
                    $collection = $wrapped->getPropertyValue($assoc['fieldName']);
                    foreach ($collection as $trans) {
                        if ($trans->getLocale() === $locale) {
                            $result[] = array(
                                        'field' => $trans->getField(),
                                        'content' => $trans->getContent()
                            );
                        }
                    }
                    $found = true;
                    break;
                }
            }
            // if collection is not set, fetch it through relation
            if (!$found) {
                $dql = 'SELECT t.content, t.field FROM ' . $translationClass . ' t';
                $dql .= ' WHERE t.locale = :locale';
                $dql .= ' AND t.object = :object';

                $q = $em->createQuery($dql);
                $q->setParameters(compact('object', 'locale'));
                $result = $q->getArrayResult();
            }
        } else {
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
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm
            ->createQueryBuilder($translationClass)
            ->field('locale')->equals($locale)
            ->field('field')->equals($field)
            ->limit(1)
        ;
        if ($this->usesPersonalTranslation($translationClass)) {
            $qb->field('object')->equals($wrapped->getObject());
        } else {
            $qb->field('foreignKey')->equals($wrapped->getIdentifier());
            $qb->field('objectClass')->equals($wrapped->getMetadata()->name);
        }
        $q = $qb->getQuery();
        $q->setHydrate(false);
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = current($result->toArray());
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass)
    {
        $dm = $this->getObjectManager();
        $qb = $dm
            ->createQueryBuilder($transClass)
            ->remove()
        ;
        if ($this->usesPersonalTranslation($transClass)) {
            $qb->field('object')->equals($wrapped->getObject());
        } else {
            $qb->field('foreignKey')->equals($wrapped->getIdentifier());
            $qb->field('objectClass')->equals($wrapped->getMetadata()->name);
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