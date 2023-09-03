<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use MongoDB\BSON\Regex;

/**
 * Doctrine event adapter for ODM adapted
 * for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ODM extends BaseAdapterODM implements SluggableAdapter
{
    public function getSimilarSlugs($object, $meta, array $config, $slug)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $qb = $dm->createQueryBuilder($config['useObjectClass']);
        if (($identifier = $wrapped->getIdentifier()) && !$meta->isIdentifier($config['slug'])) {
            $qb->field($meta->getIdentifier()[0])->notEqual($identifier);
        }
        $qb->field($config['slug'])->equals(new Regex('^'.preg_quote($slug, '/')));

        // use the unique_base to restrict the uniqueness check
        if ($config['unique'] && isset($config['unique_base'])) {
            if (is_object($ubase = $wrapped->getPropertyValue($config['unique_base']))) {
                $qb->field($config['unique_base'].'.$id')->equals(new \MongoId($ubase->getId()));
            } elseif ($ubase) {
                $qb->where('/^'.preg_quote($ubase, '/').'/.test(this.'.$config['unique_base'].')');
            } else {
                $qb->field($config['unique_base'])->equals(null);
            }
        }

        $q = $qb->getQuery();
        $q->setHydrate(false);

        return $q->getIterator()->toArray();
    }

    /**
     * This query can cause some data integrity failures since it does not
     * execute automatically
     *
     * {@inheritdoc}
     */
    public function replaceRelative($object, array $config, $target, $replacement)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata($config['useObjectClass']);

        $q = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->where("function() {
                return this.{$config['slug']}.indexOf('{$target}') === 0;
            }")
            ->getQuery()
        ;
        $q->setHydrate(false);
        $result = $q->getIterator();
        $count = 0;

        foreach ($result as $targetObject) {
            ++$count;
            $slug = preg_replace("@^{$target}@smi", $replacement.$config['pathSeparator'], $targetObject[$config['slug']]);
            $dm
                ->createQueryBuilder()
                ->updateMany($config['useObjectClass'])
                ->field($config['slug'])->set($slug)
                ->field($meta->getIdentifier()[0])->equals($targetObject['_id'])
                ->getQuery()
                ->execute()
            ;
        }

        return $count;
    }

    /**
     * This query can cause some data integrity failures since it does not
     * execute atomically
     *
     * {@inheritdoc}
     */
    public function replaceInverseRelative($object, array $config, $target, $replacement)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $meta = $dm->getClassMetadata($config['useObjectClass']);
        $q = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->field($config['mappedBy'].'.'.$meta->getIdentifier()[0])->equals($wrapped->getIdentifier())
            ->getQuery()
        ;
        $q->setHydrate(false);
        $result = $q->getIterator();
        $count = 0;

        foreach ($result as $targetObject) {
            ++$count;
            $slug = preg_replace("@^{$replacement}@smi", $target, $targetObject[$config['slug']]);
            $dm
                ->createQueryBuilder()
                ->updateMany($config['useObjectClass'])
                ->field($config['slug'])->set($slug)
                ->field($meta->getIdentifier()[0])->equals($targetObject['_id'])
                ->getQuery()
                ->execute()
            ;
        }

        return $count;
    }
}
