<?php

namespace Gedmo\Sluggable;

use Doctrine\ORM\Events,
    Doctrine\Common\EventArgs,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\Query;

/**
 * The SluggableListener handles the generation of slugs
 * for entities which implements the Sluggable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted entities.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Klein Florian <florian.klein@free.fr>
 * @subpackage SluggableListener
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener extends AbstractSluggableListener
{   
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObjectManager(EventArgs $args)
    {
        return $args->getEntityManager();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObject(EventArgs $args)
    {
        return $args->getEntity();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    public function recomputeSingleObjectChangeSet($uow, ClassMetadata $meta, $object)
    {
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getUniqueSlugResult(ObjectManager $om, $object, ClassMetadata $meta, array $config, $preferedSlug)
    {
        $qb = $om->createQueryBuilder();
        $qb->select('rec.' . $config['slug'])
            ->from($meta->name, 'rec')
            ->where($qb->expr()->like(
                'rec.' . $config['slug'], 
                $qb->expr()->literal($preferedSlug . '%'))
            );
        // include identifiers
        $entityIdentifiers = $meta->getIdentifierValues($object);
        foreach ($entityIdentifiers as $field => $value) {
            if (strlen($value)) {
                $qb->andWhere('rec.' . $field . ' <> ' . $value);
            }
        }
        $q = $qb->getQuery();
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        return $q->execute();
    }
}