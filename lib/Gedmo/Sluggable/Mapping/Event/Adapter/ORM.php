<?php

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\ORM\Query;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;

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
final class ORM extends BaseAdapterORM implements SluggableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getSimilarSlugs($object, $meta, array $config, $slug)
    {
        $em = $this->getObjectManager();
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
        foreach ((array)$entityIdentifiers as $field => $value) {
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