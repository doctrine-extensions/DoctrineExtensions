<?php

namespace Gedmo\Translatable;

use Doctrine\ORM\Events,
    Doctrine\Common\EventArgs,
    Doctrine\ORM\Query;

/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does an additional query for each field to translate.
 * 
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage TranslationListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationListener extends AbstractTranslationListener
{    
    /**
     * The translation entity class used to store the translations
     * 
     * @var string
     */
    protected $_defaultTranslationEntity = 'Gedmo\Translatable\Entity\Translation';
    
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
            Events::postPersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultTranslationClass()
    {
        return $this->_defaultTranslationEntity;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getEntityManager();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObject(EventArgs $args)
    {
        return $args->getEntity();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName($meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function removeAssociatedTranslations($om, $objectId, $transClass)
    {
        $dql = 'DELETE ' . $transClass . ' trans';
        $dql .= ' WHERE trans.foreignKey = :objectId';
            
        $q = $om->createQuery($dql);
        $q->setParameters(compact('objectId'));
        return $q->getSingleScalarResult();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function insertTranslationRecord($om, $translation)
    {
        $meta = $om->getClassMetadata(get_class($translation));        
        $data = array();

        foreach ($meta->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$meta->isIdentifier($fieldName)) {
                $data[$meta->getColumnName($fieldName)] = $reflProp->getValue($translation);
            }
        }
        
        $table = $meta->getTableName();
        if (!$om->getConnection()->insert($table, $data)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to insert new Translation record');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function findTranslation($om, $objectId, $objectClass, $locale, $field)
    {
        $qb = $om->createQueryBuilder();
        $qb->select('trans')
            ->from($this->getTranslationClass($objectClass), 'trans')
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
     * {@inheritdoc}
     */
    protected function setOriginalObjectProperty($uow, $oid, $property, $value)
    {
        $uow->setOriginalEntityProperty($oid, $property, $value);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function clearObjectChangeSet($uow, $oid)
    {
        $uow->clearEntityChangeSet($oid);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function loadTranslations($om, $object)
    {
        $objectClass = get_class($object);
        $meta = $om->getClassMetadata($objectClass);
        $locale = strtolower($this->getTranslatableLocale($object, $meta));
        $this->validateLocale($locale);
        
        // there should be single identifier
        $identifierField = $this->getSingleIdentifierFieldName($meta);
        // load translated content for all translatable fields
        $translationClass = $this->getTranslationClass($objectClass);
        $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
        // construct query
        $dql = 'SELECT t.content, t.field FROM ' . $translationClass . ' t';
        $dql .= ' WHERE t.foreignKey = :objectId';
        $dql .= ' AND t.locale = :locale';
        $dql .= ' AND t.objectClass = :objectClass';
        // fetch results
        $q = $om->createQuery($dql);
        $q->setParameters(compact('objectId', 'locale', 'objectClass'));
        return $q->getArrayResult();
    }
}