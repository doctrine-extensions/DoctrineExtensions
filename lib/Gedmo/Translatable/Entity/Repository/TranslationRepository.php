<?php

namespace Gedmo\Translatable\Entity\Repository;

use Gedmo\Translatable\TranslationListener;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Entity.Repository
 * @subpackage TranslationRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Current TranslationListener instance used
     * in EntityManager
     *
     * @var TranslationListener
     */
    private $listener;

    /**
     * Makes additional translation of $entity $field into $locale
     * using $value
     *
     * @param object $entity
     * @param string $field
     * @param string $locale
     * @param mixed $value
     * @return TranslationRepository
     */
    public function translate($entity, $field, $locale, $value)
    {
        $meta = $this->_em->getClassMetadata(get_class($entity));
        $config = $this->getTranslationListener()->getConfiguration($this->_em, $meta->name);
        if (!isset($config['fields']) || !in_array($field, $config['fields'])) {
            throw new \Gedmo\Exception\InvalidArgumentException("Entity: {$meta->name} does not translate - {$field}");
        }
        $oid = spl_object_hash($entity);
        $this->listener->addTranslation($oid, $field, $locale, $value);
        return $this;
    }
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
        $wrapped = new EntityWrapper($entity, $this->_em);
        if ($wrapped->hasValidIdentifier()) {
            $entityId = $wrapped->getIdentifier();
            $entityClass = $wrapped->getMetadata()->name;

            $translationMeta = $this->getClassMetadata(); // table inheritance support
            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($translationMeta->rootEntityName, 'trans')
                ->where('trans.foreignKey = :entityId', 'trans.objectClass = :entityClass')
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
     * Find the entity $class by the translated field.
     * Result is the first occurence of translated field.
     * Query can be slow, since there are no indexes on such
     * columns
     *
     * @param string $field
     * @param string $value
     * @param string $class
     * @return object - instance of $class or null if not found
     */
    public function findObjectByTranslatedField($field, $value, $class)
    {
        $entity = null;
        $meta = $this->_em->getClassMetadata($class);
        $translationMeta = $this->getClassMetadata(); // table inheritance support
        if ($meta->hasField($field)) {
            $dql = "SELECT trans.foreignKey FROM {$translationMeta->rootEntityName} trans";
            $dql .= ' WHERE trans.objectClass = :class';
            $dql .= ' AND trans.field = :field';
            $dql .= ' AND trans.content = :value';
            $q = $this->_em->createQuery($dql);
            $q->setParameters(compact('class', 'field', 'value'));
            $q->setMaxResults(1);
            $result = $q->getArrayResult();
            $id = count($result) ? $result[0]['foreignKey'] : null;

            if ($id) {
                $entity = $this->_em->find($class, $id);
            }
        }
        return $entity;
    }

    /**
     * Loads all translations with all translatable
     * fields by a given entity primary key
     *
     * @param mixed $id - primary key value of an entity
     * @return array
     */
    public function findTranslationsByObjectId($id)
    {
        $result = array();
        if ($id) {
            $translationMeta = $this->getClassMetadata(); // table inheritance support
            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($translationMeta->rootEntityName, 'trans')
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

    /**
     * Get the currently used TranslationListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return TranslationListener
     */
    private function getTranslationListener()
    {
        if (!$this->listener) {
            foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof TranslationListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The translation listener could not be found');
            }
        }
        return $this->listener;
    }
}