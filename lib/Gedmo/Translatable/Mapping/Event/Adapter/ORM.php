<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine event adapter for ORM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Translatable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements TranslatableAdapter
{
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
    public function loadTranslations($object, $translationClass, $locale)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrapp($object, $em);
        // load translated content for all translatable fields
        $objectId = $wrapped->getIdentifier();
        // construct query
        $dql = 'SELECT t.content, t.field FROM ' . $translationClass . ' t';
        $dql .= ' WHERE t.foreignKey = :objectId';
        $dql .= ' AND t.locale = :locale';
        $dql .= ' AND t.objectClass = :objectClass';
        // fetch results
        $objectClass = $wrapped->getMetadata()->name;
        $q = $em->createQuery($dql);
        $q->setParameters(compact('objectId', 'locale', 'objectClass'));
        return $q->getArrayResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation($objectId, $objectClass, $locale, $field, $translationClass)
    {
        $em = $this->getObjectManager();
        $qb = $em->createQueryBuilder();
        $qb->select('trans')
            ->from($translationClass, 'trans')
            ->where(
                'trans.foreignKey = :objectId',
                'trans.locale = :locale',
                'trans.field = :field',
                'trans.objectClass = :objectClass'
            );
        $q = $qb->getQuery();
        $result = $q->execute(
            compact('field', 'locale', 'objectId', 'objectClass'),
            Query::HYDRATE_OBJECT
        );

        if ($result) {
            return array_shift($result);
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAssociatedTranslations($objectId, $transClass, $targetClass)
    {
        $em = $this->getObjectManager();
        $dql = 'DELETE ' . $transClass . ' trans';
        $dql .= ' WHERE trans.foreignKey = :objectId';
        $dql .= ' AND trans.objectClass = :targetClass';

        $q = $em->createQuery($dql);
        $q->setParameters(compact('objectId', 'targetClass'));
        return $q->getSingleScalarResult();
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
        $wrapped = AbstractWrapper::wrapp($object, $em);
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
        $wrapped = AbstractWrapper::wrapp($object, $em);
        $meta = $wrapped->getMetadata();
        $type = Type::getType($meta->getTypeOfField($field));
        $value = $type->convertToPHPValue($value, $em->getConnection()->getDatabasePlatform());
        $wrapped->setPropertyValue($field, $value);
    }
}