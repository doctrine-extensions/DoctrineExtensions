<?php

namespace Gedmo\Sortable\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\EventArgs,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Cursor,
    Gedmo\Sortable\AbstractSortableListener;

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
class SortableListener extends AbstractSortableListener
{
    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            //Events::postLoad,
            //Events::postPersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }

    public function updateSortableSort($om, $object, $sort)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        $id = $meta->getIdentifierObject($object);

        $collection = $om->getDocumentCollection($meta->name);
        $collection->update(array('_id' => $id), array('$set' => array($config['sort'] => $sort)));
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSortableAfterObject($object, $om, $identifier = null)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        if (!isset($config['sort'])) {
            die(var_dump($this->getConfiguration($om, 'Design\PageBundle\Document\Block')));
            die(var_dump($config));
        }
        $objectPosition = $meta->getReflectionProperty($config['sort'])->getValue($object);

        $qb = $om->createQueryBuilder(get_class($object))
            ->field($config['sort'])->gt($objectPosition);

        if (isset($config['sort_identifier'])) {

            if (null === $identifier) {
                $identifier = $this->getParentIdentifier($object, $om);
            }

            $sortIdField = $config['sort_identifier'];
            $mapping = $meta->fieldMappings[$sortIdField];

            if ($mapping['reference'] /*|| $mapping['embedded']*/ && $mapping['type'] == 'one') {
                $qb->field($sortIdField.'.id')->equals($identifier);
            } else {
                throw new \Exception('unsupported mapping');
            }
        }

        $result = $qb->getQuery()->execute();

        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }

        return $result;
    }



    /**
     * {@inheritdoc}
     */
    protected function getFinalPosition($om, $object)
    {
        $qb = $om->createQueryBuilder(get_class($object));
        $result = $qb->getQuery()->execute();

        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }

        return count($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentIdentifier($object, $om)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        if (!isset($config['sort_identifier'])) {
            return null;
        }

        $sortIdField = $config['sort_identifier'];
        $reference = $meta->getReflectionProperty($sortIdField)->getValue($object);

        if (null === $reference) {
            return null;
        }

        $id = $reference->getId();
        
        return null === $id ? spl_object_hash($reference) : $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function countSortableForIdentifier($identifier, $object, $om)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        $qb = $om->createQueryBuilder(get_class($object))
            ->field($config['sort_identifier'].'.id')->equals($identifier);
        
        $result = $qb->getQuery()->execute();

        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }

        return count($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function recomputeSingleObjectChangeSet($uow, ClassMetadata $meta, $object)
    {
        //return $uow->computeChangeSet($meta, $object);
        return $uow->recomputeSingleDocumentChangeSet($meta, $object);
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
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
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
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName(ClassMetadata $meta)
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
}