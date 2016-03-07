<?php

namespace Gedmo\Sortable\Mapping\Event\Adapter;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
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
        $qb->select('MAX(n.' . $config['position'] . ')')
            ->from($config['useObjectClass'], 'n');
        $this->addGroupWhere($qb, $groups, $meta);
        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->useResultCache(false);
        $res = $query->getResult();

        return $res[0][1];
    }

    private function addGroupWhere(QueryBuilder $qb, $groups, $meta)
    {
        $i = 1;
        foreach ($groups as $group => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull('n.' . $group));
            } else {
                $qb->andWhere('n.' . $group . ' = :group__' . $i);
                $qb->setParameter('group__' . $i, $this->getGroupValue($value), $this->getGroupType($group, $value, $meta));
            }
            $i++;
        }
    }

    /**
     * @param $value
     * @return bool
     */
    private function isEntity($value)
    {
        return (is_object($value) && $this->getObjectManager()->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value)));
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getGroupValue($value)
    {
        if (!$this->isEntity($value)) {
            return $value;
        }
        return $this->getObjectManager()->getUnitOfWork()->getSingleIdentifierValue($value);
    }

    /**
     * @param $value
     * @return \Doctrine\DBAL\Types\Type|null|string
     */
    private function getGroupType($group, $value, $meta)
    {
        if (!$this->isEntity($value)) {
            if ($meta instanceof ClassMetadata) {
                return $meta->getTypeOfField($group);
            }
            return null;
        }

        $metaData = $this->getObjectManager()->getClassMetadata(ClassUtils::getClass($value));
        $ids = $metaData->getIdentifier();
        if (count($ids) > 1) {
            return null;
        }
        return $metaData->getTypeOfField($ids[0]);
    }

    public function updatePositions($relocation, $delta, $config)
    {
        $sign = $delta['delta'] < 0 ? "-" : "+";
        $absDelta = abs($delta['delta']);
        $dql = "UPDATE {$relocation['name']} n";
        $dql .= " SET n.{$config['position']} = n.{$config['position']} {$sign} {$absDelta}";
        $dql .= " WHERE n.{$config['position']} >= {$delta['start']}";
        // if not null, false or 0
        if ($delta['stop'] > 0) {
            $dql .= " AND n.{$config['position']} < {$delta['stop']}";
        }
        $i = -1;
        $params = array();
        foreach ($relocation['groups'] as $group => $value) {
            if (null === $value) {
                $dql .= " AND n.{$group} IS NULL";
            } else {
                $dql .= " AND n.{$group} = :val___" . (++$i);
                $params['val___' . $i] = $value;
            }
        }

        $meta = $this->getObjectManager()->getClassMetadata($relocation['name']);
        // add excludes
        if (!empty($delta['exclude'])) {
            $meta = $this->getObjectManager()->getClassMetadata($relocation['name']);
            if (count($meta->identifier) == 1) {
                // if we only have one identifier, we can use IN syntax, for better performance
                $excludedIds = array();
                foreach ($delta['exclude'] as $entity) {
                    if ($id = $meta->getFieldValue($entity, $meta->identifier[0])) {
                        $excludedIds[] = $id;
                    }
                }
                if (!empty($excludedIds)) {
                    $params['excluded'] = $excludedIds;
                    $dql .= " AND n.{$meta->identifier[0]} NOT IN (:excluded)";
                }
            } else {
                if (count($meta->identifier) > 1) {
                    foreach ($delta['exclude'] as $entity) {
                        $j = 0;
                        $dql .= " AND NOT (";
                        foreach ($meta->getIdentifierValues($entity) as $id => $value) {
                            $dql .= ($j > 0 ? " AND " : "") . "n.{$id} = :val___" . (++$i);
                            $params['val___' . $i] = $value;
                            $j++;
                        }
                        $dql .= ")";
                    }
                }
            }
        }

        $em = $this->getObjectManager();
        $q = $em->createQuery($dql);
        $q->setParameters($params);
        foreach ($relocation['groups'] as $group => $value) {
            if (!is_null($value)) {
                $q->setParameter('val___' . $i, $this->getGroupValue($value), $this->getGroupType($group, $value, $meta));
            }
        }
        $q->getSingleScalarResult();
    }
}
