<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query;

/**
 * The SluggableListener handles the generation of slugs
 * for entities which implements the Sluggable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted entities.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage SluggableListener
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener extends MappedEventSubscriber implements EventSubscriber
{
    /**
     * The power exponent to jump
     * the slug unique number by tens.
     * 
     * @var integer
     */
    private $exponent = 0;
    
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
     * {@inheritDoc}
     */
    protected function _getNamespace()
    {
        return __NAMESPACE__;
    }
    
    /**
     * Checks for persisted entity to specify slug
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        
        if ($config = $this->getConfiguration($em, get_class($entity))) {
            $this->_generateSlug($em, $entity, false);
        }
    }
    
    /**
     * Generate slug on entities being updated during flush
     * if they require changing
     * 
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($config = $this->getConfiguration($em, get_class($entity))) {
                if ($config['updatable']) {
                    $this->_generateSlug($em, $entity, $uow->getEntityChangeSet($entity));
                }
            }
        }
    }
    
    /**
     * Creates the slug for entity being flushed
     * 
     * @param EntityManager $em
     * @param object $entity
     * @param mixed $changeSet
     *      case array: the change set array
     *      case boolean(false): entity is not managed
     * @throws UnexpectedValueException - if parameters are missing
     *      or invalid
     * @return void
     */
    protected function _generateSlug(EntityManager $em, $entity, $changeSet)
    {
        $entityClass = get_class($entity);
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata($entityClass);
        $config = $this->getConfiguration($em, $entityClass);
        
        // collect the slug from fields
        $slug = '';
        $needToChangeSlug = false;
        foreach ($config['fields'] as $sluggableField) {
            if ($changeSet === false || isset($changeSet[$sluggableField])) {
                $needToChangeSlug = true;
            }
            $slug .= $meta->getReflectionProperty($sluggableField)->getValue($entity) . ' ';
        }
        // if slug is not changed, no need further processing
        if (!$needToChangeSlug) {
            return; // nothing to do
        }
        
        if (!strlen(trim($slug))) {
            throw new \Gedmo\Exception\UnexpectedValueException('Unable to find any non empty sluggable fields, make sure they have something at least.');
        }
        
        // build the slug
        $slug = call_user_func_array(
            array('Gedmo\Sluggable\Util\Urlizer', 'urlize'), 
            array($slug, $config['separator'], $entity)
        );

        // stylize the slug
        switch ($config['style']) {
            case 'camel':
                $slug = preg_replace_callback(
                    '@^[a-z]|' . $config['separator'] . '[a-z]@smi', 
                    create_function('$m', 'return strtoupper($m[0]);'), 
                    $slug
                );
                break;
                
            default:
                // leave it as is
                break;
        }
        
        // cut slug if exceeded in length
        $mapping = $meta->getFieldMapping($config['slug']);
        if (strlen($slug) > $mapping['length']) {
            $slug = substr($slug, 0, $mapping['length']);
        }

        // make unique slug if requested
        if ($config['unique']) {
            $this->exponent = 0;
            $slug = $this->_makeUniqueSlug($em, $entity, $slug);
        }
        // set the final slug
        $meta->getReflectionProperty($config['slug'])->setValue($entity, $slug);
        // recompute changeset if entity is managed
        if ($changeSet !== false) {
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }
    
    /**
     * Generates the unique slug
     * 
     * @param EntityManager $em
     * @param object $entity
     * @param string $preferedSlug
     * @return string - unique slug
     */
    protected function _makeUniqueSlug(EntityManager $em, $entity, $preferedSlug)
    {        
        $entityClass = get_class($entity);
        $meta = $em->getClassMetadata($entityClass);
        $config = $this->getConfiguration($em, $entityClass);
        
        // search for similar slug
        $qb = $em->createQueryBuilder();
        $qb->select('rec.' . $config['slug'])
            ->from($entityClass, 'rec')
            ->where($qb->expr()->like(
                'rec.' . $config['slug'], 
                $qb->expr()->literal($preferedSlug . '%'))
            );
        // include identifiers
        $entityIdentifiers = $meta->getIdentifierValues($entity);
        foreach ($entityIdentifiers as $field => $value) {
            if (strlen($value)) {
                $qb->andWhere('rec.' . $field . ' <> ' . $value);
            }
        }
        $q = $qb->getQuery();
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        $result = $q->execute();

        if ($result) {
            $generatedSlug = $preferedSlug;
            $sameSlugs = array();
            foreach ((array)$result as $list) {
                $sameSlugs[] = $list[$config['slug']];
            }

            $i = pow(10, $this->exponent);
            do {
                $generatedSlug = $preferedSlug . $config['separator'] . $i++;
            } while (in_array($generatedSlug, $sameSlugs));

            $mapping = $meta->getFieldMapping($config['slug']);
            $needRecursion = false;
            if (strlen($generatedSlug) > $mapping['length']) {
                $needRecursion = true;
                $generatedSlug = substr(
                    $generatedSlug, 
                    0, 
                    $mapping['length'] - (strlen($i) + strlen($config['separator']))
                );
                $this->exponent = strlen($i) - 1;
            }
            
            if ($needRecursion) {
                $generatedSlug = $this->_makeUniqueSlug($em, $entity, $generatedSlug);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }
}