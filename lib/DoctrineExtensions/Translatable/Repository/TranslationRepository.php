<?php

namespace DoctrineExtensions\Translatable\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query,
    DoctrineExtensions\Translatable\Exception,
    DoctrineExtensions\Translatable\Translatable;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable.Repository
 * @subpackage TranslationRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationRepository extends EntityRepository
{    
    /**
     * Loads all translations with all translatable
     * fields from the given entity
     * 
     * @param object $entity Must implement Translatable
     * @return array list of translations in locale groups
     */
    public function findTranslations($entity)
    {
        $result = array();
        if ($entity instanceof Translatable) {
            if ($this->_em->getUnitOfWork()->getEntityState($entity) == \Doctrine\ORM\UnitOfWork::STATE_NEW) {
                return $result;
            }
            $entityClass = get_class($entity);
            // no need cache, metadata is loaded only once in MetadataFactoryClass
            //$translationMetadata = $em->getClassMetadata(self::TRANSLATION_ENTITY_CLASS);
            $entityClassMetadata = $this->_em->getClassMetadata($entityClass);
            // check for the availability of the primary key
            $entityId = $entityClassMetadata->getIdentifierValues($entity);
            if (count($entityId) == 1 && current($entityId)) {
                $entityId = current($entityId);
            } else {
                throw Exception::singleIdentifierRequired($entityClass);
            }
            
            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($this->_entityName, 'trans')
                ->where('trans.foreignKey = :entityId', 'trans.entity = :entityClass')
                ->orderBy('trans.locale');
            $q = $qb->getQuery();
            $data = $q->execute(
                compact('entityId', 'entityClass'),
                Query::HYDRATE_ARRAY
            );
            
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }
        return $result;
    }
    
    /**
     * Loads all translations with all translatable
     * fields by a given entity primary key
     *
     * @param mixed $id - primary key value of an entity
     * @return array
     */
    public function findTranslationsByEntityId($id)
    {
        $result = array();
        if ($id) {            
            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($this->_entityName, 'trans')
                ->where('trans.foreignKey = :entityId')
                ->orderBy('trans.locale');
            $q = $qb->getQuery();
            $data = $q->execute(
                array('entityId' => $id),
                Query::HYDRATE_ARRAY
            );
            
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }
        return $result;
    }
}