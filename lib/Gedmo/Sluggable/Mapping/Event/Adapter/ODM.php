<?php

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Doctrine event adapter for ODM adapted
 * for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements SluggableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getSimilarSlugs($object, $meta, array $config, $slug)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $qb = $dm->createQueryBuilder($config['useObjectClass']);
        if (($identifier = $wrapped->getIdentifier()) && !$meta->isIdentifier($config['slug'])) {
            $qb->field($meta->identifier)->notEqual($identifier);
        }
        $qb->field($config['slug'])->equals(new \MongoRegex('/^' . preg_quote($slug, '/') . '/'));

        // use the unique_base to restrict the uniqueness check
        if ($config['unique'] && isset($config['unique_base'])) {
            if (is_object($ubase = $wrapped->getPropertyValue($config['unique_base']))) {
                $qb->field($config['unique_base'] . '.$id')->equals(new \MongoId($ubase->getId()));
            } elseif ($ubase) {
                $qb->where('/^' . preg_quote($ubase, '/') . '/.test(this.' . $config['unique_base'] . ')');
            } else {
                $qb->field($config['unique_base'])->equals(null);
            }
        }

        $q = $qb->getQuery();
        $q->setHydrate(false);

        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * This query can cause some data integrity failures since it does not
     * execute automatically
     *
     * {@inheritDoc}
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
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
            foreach ($result as $targetObject) {
                $slug = preg_replace("@^{$target}@smi", $replacement.$config['pathSeparator'], $targetObject[$config['slug']]);
                $dm
                    ->createQueryBuilder()
                    ->update($config['useObjectClass'])
                    ->field($config['slug'])->set($slug)
                    ->field($meta->identifier)->equals($targetObject['_id'])
                    ->getQuery()
                    ->execute()
                ;
            }
        }
    }

    /**
     * This query can couse some data integrity failures since it does not
     * execute atomicaly
     *
     * {@inheritDoc}
     */
    public function replaceInverseRelative($object, array $config, $target, $replacement)
    {
        $dm = $this->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $dm);
        $meta = $dm->getClassMetadata($config['useObjectClass']);
        $q = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->field($config['mappedBy'].'.'.$meta->identifier)->equals($wrapped->getIdentifier())
            ->getQuery()
        ;
        $q->setHydrate(false);
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
            foreach ($result as $targetObject) {
                $slug = preg_replace("@^{$replacement}@smi", $target, $targetObject[$config['slug']]);
                $dm
                    ->createQueryBuilder()
                    ->update($config['useObjectClass'])
                    ->field($config['slug'])->set($slug)
                    ->field($meta->identifier)->equals($targetObject['_id'])
                    ->getQuery()
                    ->execute()
                ;
            }
        }
    }
}
