<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Doctrine event adapter for ORM adapted
 * for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class ORM extends BaseAdapterORM implements SluggableAdapter
{
    public function getSimilarSlugs($object, $meta, array $config, $slug)
    {
        $em = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $em);
        $qb = $em->createQueryBuilder();
        $qb->select('rec.'.$config['slug'])
            ->from($config['useObjectClass'], 'rec')
            ->where($qb->expr()->like(
                'rec.'.$config['slug'],
                ':slug')
            )
        ;
        $qb->setParameter('slug', $slug.'%');

        // use the unique_base to restrict the uniqueness check
        if ($config['unique'] && isset($config['unique_base'])) {
            $ubase = $wrapped->getPropertyValue($config['unique_base']);
            if (array_key_exists($config['unique_base'], $wrapped->getMetadata()->getAssociationMappings())) {
                $mapping = $wrapped->getMetadata()->getAssociationMapping($config['unique_base']);
            } else {
                $mapping = false;
            }
            if (($ubase || 0 === $ubase) && !$mapping) {
                $qb->andWhere('rec.'.$config['unique_base'].' = :unique_base');
                $qb->setParameter(':unique_base', $ubase);
            } elseif ($ubase && $mapping && in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE], true)) {
                $mappedAlias = 'mapped_'.$config['unique_base'];
                $wrappedUbase = AbstractWrapper::wrap($ubase, $em);
                $metadata = $wrappedUbase->getMetadata();
                assert($metadata instanceof ClassMetadataInfo);
                $qb->innerJoin('rec.'.$config['unique_base'], $mappedAlias);
                foreach (array_keys($mapping['targetToSourceKeyColumns']) as $i => $mappedKey) {
                    $mappedProp = $metadata->getFieldName($mappedKey);
                    $qb->andWhere($qb->expr()->eq($mappedAlias.'.'.$mappedProp, ':assoc'.$i));
                    $qb->setParameter(':assoc'.$i, $wrappedUbase->getPropertyValue($mappedProp));
                }
            } else {
                $qb->andWhere($qb->expr()->isNull('rec.'.$config['unique_base']));
            }
        }

        // include identifiers
        foreach ((array) $wrapped->getIdentifier(false) as $id => $value) {
            if (!$meta->isIdentifier($config['slug'])) {
                $namedId = str_replace('.', '_', $id);
                $qb->andWhere($qb->expr()->neq('rec.'.$id, ':'.$namedId));
                $qb->setParameter($namedId, $value, $meta->getTypeOfField($namedId));
            }
        }
        $q = $qb->getQuery();
        $q->setHydrationMode(Query::HYDRATE_ARRAY);

        return $q->execute();
    }

    public function replaceRelative($object, array $config, $target, $replacement)
    {
        $em = $this->getObjectManager();
        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'rec')
            ->set('rec.'.$config['slug'], $qb->expr()->concat(
                $qb->expr()->literal($replacement),
                $qb->expr()->substring('rec.'.$config['slug'], mb_strlen($target))
            ))
            ->where($qb->expr()->like(
                'rec.'.$config['slug'],
                $qb->expr()->literal($target.'%'))
            )
        ;
        // update in memory
        $q = $qb->getQuery();

        return $q->execute();
    }

    public function replaceInverseRelative($object, array $config, $target, $replacement)
    {
        $em = $this->getObjectManager();
        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'rec')
            ->set('rec.'.$config['slug'], $qb->expr()->concat(
                $qb->expr()->literal($target),
                $qb->expr()->substring('rec.'.$config['slug'], mb_strlen($replacement) + 1)
            ))
            ->where($qb->expr()->like('rec.'.$config['slug'], $qb->expr()->literal($replacement.'%')))
        ;
        $q = $qb->getQuery();

        return $q->execute();
    }
}
