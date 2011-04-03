<?php

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;

/**
 * Doctrine event adapter for ORM adapted
 * for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Sluggable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM
{
    /**
     * Loads the similar slugs
     *
     * @param EntityManager $em
     * @param object $object
     * @param ClassMetadataInfo $meta
     * @param array $config
     * @param string $slug
     * @return array
     */
    public function getSimilarSlugs(EntityManager $em, $object, ClassMetadataInfo $meta, array $config, $slug)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('rec.' . $config['slug'])
            ->from($meta->name, 'rec')
            ->where($qb->expr()->like(
                'rec.' . $config['slug'],
                $qb->expr()->literal($slug . '%'))
            );
        // include identifiers
        $entityIdentifiers = $this->extractIdentifier($em, $object, false);
        $parameters = array();
        foreach ($entityIdentifiers as $field => $value) {
            if (strlen($value)) {
                $qb->andWhere('rec.' . $field . ' <> :' . $field);
                $parameters[$field] = $value;
            }
        }
        $q = $qb->getQuery();
        if ($parameters) {
            $q->setParameters($parameters);
        }
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        return $q->execute();
    }
}