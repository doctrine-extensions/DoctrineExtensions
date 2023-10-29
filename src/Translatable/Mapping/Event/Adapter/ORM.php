<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ORM extends BaseAdapterORM implements TranslatableAdapter
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
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $result = [];
        if ($this->usesPersonalTranslation($translationClass)) {
            // first try to load it using collection
            $found = false;
            $metadata = $wrapped->getMetadata();
            assert($metadata instanceof ClassMetadataInfo);
            foreach ($metadata->getAssociationMappings() as $assoc) {
                $isRightCollection = $assoc['targetEntity'] === $translationClass
                    && 'object' === $assoc['mappedBy']
                    && ClassMetadataInfo::ONE_TO_MANY === $assoc['type']
                ;
                if ($isRightCollection) {
                    $collection = $wrapped->getPropertyValue($assoc['fieldName']);
                    foreach ($collection as $trans) {
                        if ($trans->getLocale() === $locale) {
                            $result[] = [
                                'field' => $trans->getField(),
                                'content' => $trans->getContent(),
                            ];
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
                $q->setParameters([
                    'object' => $object,
                    'locale' => $locale,
                ]);
                $result = $q->getArrayResult();
            }
        } else {
            // load translated content for all translatable fields
            $objectId = $this->foreignKey($wrapped->getIdentifier(), $translationClass);
            // construct query
            $dql = 'SELECT t.content, t.field FROM '.$translationClass.' t';
            $dql .= ' WHERE t.foreignKey = :objectId';
            $dql .= ' AND t.locale = :locale';
            $dql .= ' AND t.objectClass = :objectClass';
            // fetch results
            $q = $em->createQuery($dql);
            $q->setParameters([
                'objectId' => $objectId,
                'locale' => $locale,
                'objectClass' => $objectClass,
            ]);
            $result = $q->getArrayResult();
        }

        return $result;
    }

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
                            $objectId = $this->foreignKey($wrapped->getIdentifier(), $translationClass);
                            $isRequestedTranslation = $trans->getForeignKey() === $objectId
                                && $trans->getObjectClass() === $wrapped->getMetadata()->getName()
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
            ->setParameter('locale', $locale)
            ->setParameter('field', $field)
        ;

        if ($this->usesPersonalTranslation($translationClass)) {
            $qb->andWhere('trans.object = :object');
            if ($wrapped->getIdentifier()) {
                $qb->setParameter('object', $wrapped->getObject());
            } else {
                $qb->setParameter('object', null);
            }
        } else {
            $qb->andWhere('trans.foreignKey = :objectId');
            $qb->andWhere('trans.objectClass = :objectClass');
            $qb->setParameter('objectId', $this->foreignKey($wrapped->getIdentifier(), $translationClass));
            $qb->setParameter('objectClass', $objectClass);
        }
        $q = $qb->getQuery();
        $q->setMaxResults(1);

        return $q->getOneOrNullResult();
    }

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
            $qb->setParameter('objectId', $this->foreignKey($wrapped->getIdentifier(), $transClass));
            $qb->setParameter('class', $objectClass);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function insertTranslationRecord($translation)
    {
        $em = $this->getObjectManager();
        $meta = $em->getClassMetadata(get_class($translation));
        $data = [];

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->getColumnName($fieldName)] = $reflProp->getValue($translation);
            }
        }

        $table = $meta->getTableName();
        if (!$em->getConnection()->insert($table, $data)) {
            throw new RuntimeException('Failed to insert new Translation record');
        }
    }

    public function getTranslationValue($object, $field, $value = false)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $meta = $wrapped->getMetadata();
        $type = Type::getType($meta->getTypeOfField($field));
        if (false === $value) {
            $value = $wrapped->getPropertyValue($field);
        }

        return $type->convertToDatabaseValue($value, $em->getConnection()->getDatabasePlatform());
    }

    public function setTranslationValue($object, $field, $value)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $meta = $wrapped->getMetadata();
        $type = Type::getType($meta->getTypeOfField($field));
        $value = $type->convertToPHPValue($value, $em->getConnection()->getDatabasePlatform());
        $wrapped->setPropertyValue($field, $value);
    }

    /**
     * Transforms foreing key of translation to appropriate PHP value
     * to prevent database level cast
     *
     * @param mixed  $key       foreign key value
     * @param string $className translation class name
     *
     * @phpstan-param class-string $className translation class name
     *
     * @return int|string transformed foreign key
     */
    private function foreignKey($key, string $className)
    {
        $em = $this->getObjectManager();
        $meta = $em->getClassMetadata($className);
        $type = Type::getType($meta->getTypeOfField('foreignKey'));
        switch ($type->getName()) {
            case Types::BIGINT:
            case Types::INTEGER:
            case Types::SMALLINT:
                return (int) $key;
            default:
                return (string) $key;
        }
    }
}
