<?php

namespace Gedmo\Translatable\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\EventArgs,
    Doctrine\ODM\MongoDB\Cursor,
    Gedmo\Translatable\AbstractTranslationListener;

/**
 * The translation listener handles the generation and
 * loading of translations for documents.
 * 
 * This behavior can inpact the performance of your application
 * since it does an additional query for fields to be translated.
 * 
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all document annotations since
 * the caching is activated for metadata
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.ODM.MongoDB
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
    protected $defaultTranslationDocument = 'Gedmo\Translatable\Document\Translation';
    
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
        return $this->defaultTranslationDocument;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getDocumentManager();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObject(EventArgs $args)
    {
        return $args->getDocument();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName($meta)
    {
        return $meta->identifier;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function setOriginalObjectProperty($uow, $oid, $property, $value)
    {
        $uow->setOriginalDocumentProperty($oid, $property, $value);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function clearObjectChangeSet($uow, $oid)
    {
        $uow->clearDocumentChangeSet($oid);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function removeAssociatedTranslations($om, $objectId, $transClass)
    {
        $qb = $om->createQueryBuilder($transClass);
        $q = $qb->remove()
            ->field('foreignKey')->equals($objectId)
            ->getQuery();
        return $q->execute();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function insertTranslationRecord($om, $translation)
    {
        $meta = $om->getClassMetadata(get_class($translation));        
        $collection = $om->getDocumentCollection($meta->name);
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
     * {@inheritdoc}
     */
    protected function findTranslation($om, $objectId, $objectClass, $locale, $field)
    {
        $translationClass = $this->getTranslationClass($objectClass);
        $qb = $om->createQueryBuilder($translationClass);
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
     * {@inheritdoc}
     */
    protected function loadTranslations($om, $object)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $locale = strtolower($this->getTranslatableLocale($object, $meta));
        $this->validateLocale($locale);
        
        // load translated content for all translatable fields
        $translationClass = $this->getTranslationClass($meta->name);
        $identifier = $meta->getIdentifierValue($object);
        // construct query
        $qb = $om->createQueryBuilder($translationClass);
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
}