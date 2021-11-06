<?php

namespace Gedmo\Sortable\Mapping\Event\Adapter;

use Doctrine\ORM\QueryBuilder;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Sortable\Mapping\Event\SortableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for sortable behavior
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements SortableAdapter
{
    public function getMaxPosition(array $config, $meta, $groups)
    {
        $em = $this->getObjectManager();

        $qb = $em->createQueryBuilder();
        $qb->select('MAX(n.'.$config['position'].')')
           ->from($config['useObjectClass'], 'n');
        $this->addGroupWhere($qb, $groups);
        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->useResultCache(false);
        $res = $query->getResult();

        return $res[0][1];
    }

    private function addGroupWhere(QueryBuilder $qb, $groups)
    {
        $i = 1;
        foreach ($groups as $group => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull('n.'.$group));
            } else {
                $qb->andWhere('n.'.$group.' = :group__'.$i);
                $qb->setParameter('group__'.$i, $value);
            }
            ++$i;
        }
    }

    public function updatePositions($relocation, $delta, $config)
    {
        $sign = $delta['delta'] < 0 ? '-' : '+';
        $absDelta = abs($delta['delta']);
        $dql = "UPDATE {$relocation['name']} n";
        $dql .= " SET n.{$config['position']} = n.{$config['position']} {$sign} {$absDelta}";
        $dql .= " WHERE n.{$config['position']} >= {$delta['start']}";
        // if not null, false or 0
        if ($delta['stop'] > 0) {
            $dql .= " AND n.{$config['position']} < {$delta['stop']}";
        }
        $i = -1;
        $params = [];
        foreach ($relocation['groups'] as $group => $value) {
            if (null === $value) {
                $dql .= " AND n.{$group} IS NULL";
            } else {
                $dql .= " AND n.{$group} = :val___".(++$i);
                $params['val___'.$i] = $value;
            }
        }

        // add excludes
        if (!empty($delta['exclude'])) {
            $meta = $this->getObjectManager()->getClassMetadata($relocation['name']);
            if (1 == count($meta->identifier)) {
                // if we only have one identifier, we can use IN syntax, for better performance
                $excludedIds = [];
                foreach ($delta['exclude'] as $entity) {
                    if ($id = $meta->getFieldValue($entity, $meta->identifier[0])) {
                        $excludedIds[] = $id;
                    }
                }
                if (!empty($excludedIds)) {
                    $params['excluded'] = $excludedIds;
                    $dql .= " AND n.{$meta->identifier[0]} NOT IN (:excluded)";
                }
            } elseif (count($meta->identifier) > 1) {
                foreach ($delta['exclude'] as $entity) {
                    $j = 0;
                    $dql .= ' AND NOT (';
                    foreach ($meta->getIdentifierValues($entity) as $id => $value) {
                        $dql .= ($j > 0 ? ' AND ' : '')."n.{$id} = :val___".(++$i);
                        $params['val___'.$i] = $value;
                        ++$j;
                    }
                    $dql .= ')';
                }
            }
        }

        $em = $this->getObjectManager();
        $q = $em->createQuery($dql);
        $q->setParameters($params);
        $q->getSingleScalarResult();
    }
}
