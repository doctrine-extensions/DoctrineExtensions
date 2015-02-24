<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Doctrine event adapter for ORM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements TranslatableAdapter
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
            ->isSubclassOf('Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation')
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultTranslationClass()
    {
        return 'Gedmo\\Translatable\\Entity\\Translation';
    }

    /**
     * {@inheritDoc}
     */
    public function loadTranslations($object, $translationClass, $locale, $objectClass)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $result = array();
        if ($this->usesPersonalTranslation($translationClass)) {
            // first try to load it using collection
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
                                'content' => $trans->getContent(),
                            );
                        }
                    }
                    $found = true;
                    break;
                }
            }
            // if collection is not set, fetch it through relation
            if (!$found) {
                $dql = 'SELECT t.content, t.field FROM '.$translationClass.' t';
                $dql .= ' WHERE t.locale = :locale';
                $dql .= ' AND t.object = :object';

                $q = $em->createQuery($dql);
                $q->setParameters(compact('object', 'locale'));
                $result = $q->getArrayResult();
            }
        } else {
            // load translated content for all translatable fields
            $objectId = $wrapped->getIdentifier();
            // construct query
            $dql = 'SELECT t.content, t.field FROM '.$translationClass.' t';
            $dql .= ' WHERE t.foreignKey = :objectId';
            $dql .= ' AND t.locale = :locale';
            $dql .= ' AND t.objectClass = :objectClass';
            // fetch results
            $q = $em->createQuery($dql);
            $q->setParameters(compact('objectId', 'locale', 'objectClass'));
            $result = $q->getArrayResult();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass, $objectClass)
    {
        $em = $this->getObjectManager();
        // first look in identityMap, will save one SELECT query
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $objects) {
            if ($className === $translationClass) {
                foreach ($objects as $trans) {
                    $isRequestedTranslation = !$trans instanceof Proxy
                        && $trans->getLocale() === $locale
                        && $trans->getField() === $field
                    ;
                    if ($isRequestedTranslation) {
                        if ($this->usesPersonalTranslation($translationClass)) {
                            $isRequestedTranslation = $trans->getObject() === $wrapped->getObject();
                        } else {
                            $isRequestedTranslation = $trans->getForeignKey() === $wrapped->getIdentifier()
                                && $trans->getObjectClass() === $wrapped->getMetadata()->name
                            ;
                        }
                    }
                    if ($isRequestedTranslation) {
                        return $trans;
                    }
                }
            }
        }

        $qb = $em->createQueryBuilder();
        $qb->select('trans')
            ->from($translationClass, 'trans')
            ->where(
                'trans.locale = :locale',
                'trans.field = :field'
            )
        ;
        $qb->setParameters(compact('locale', 'field'));
        if ($this->usesPersonalTranslation($translationClass)) {
            $qb->andWhere('trans.object = :object');
            if($wrapped->getIdentifier()) {
                $qb->setParameter('object', $wrapped->getObject());
            } else {
                $qb->setParameter('object', null);
            }
        } else {
            $qb->andWhere('trans.foreignKey = :objectId');
            $qb->andWhere('trans.objectClass = :objectClass');
            $qb->setParameter('objectId', $wrapped->getIdentifier());
            $qb->setParameter('objectClass', $objectClass);
        }
        $q = $qb->getQuery();
        $q->setMaxResults(1);
        $result = $q->getResult();

        if ($result) {
            return array_shift($result);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass, $objectClass)
    {
        $qb = $this
            ->getObjectManager()
            ->createQueryBuilder()
            ->delete($transClass, 'trans')
        ;
        if ($this->usesPersonalTranslation($transClass)) {
            $qb->where('trans.object = :object');
            $qb->setParameter('object', $wrapped->getObject());
        } else {
            $qb->where(
                'trans.foreignKey = :objectId',
                'trans.objectClass = :class'
            );
            $qb->setParameter('objectId', $wrapped->getIdentifier());
            $qb->setParameter('class', $objectClass);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function insertTranslationRecord($translation)
    {
        $em = $this->getObjectManager();
        $meta = $em->getClassMetadata(get_class($translation));
        $data = array();

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->getColumnName($fieldName)] = $reflProp->getValue($translation);
            }
        }

        $table = $meta->getTableName();
        if (!$em->getConnection()->insert($table, $data)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Translation record');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslationValue($object, $field, $value = false)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $meta = $wrapped->getMetadata();
        $type = Type::getType($meta->getTypeOfField($field));
        if ($value === false) {
            $value = $wrapped->getPropertyValue($field);
        }

        return $type->convertToDatabaseValue($value, $em->getConnection()->getDatabasePlatform());
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslationValue($object, $field, $value)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $meta = $wrapped->getMetadata();
        $type = Type::getType($meta->getTypeOfField($field));
        $value = $type->convertToPHPValue($value, $em->getConnection()->getDatabasePlatform());
        $wrapped->setPropertyValue($field, $value);
    }
}
