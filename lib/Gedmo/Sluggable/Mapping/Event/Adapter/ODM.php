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
 * @package Gedmo\Sluggable\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
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
        $q = $qb->getQuery();
        $q->setHydrate(false);

        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * This query can couse some data integrity failures since it does not
     * execute atomicaly
     *
     * {@inheritDoc}
     */
    public function replaceRelative($object, $slugField, $useObjectClass, $pathSeparator, $target, $replacement)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata($useObjectClass);

        $q = $dm
            ->createQueryBuilder($useObjectClass)
            ->where("function() {
                return this.{$slugField}.indexOf('{$target}') === 0;
            }")
            ->getQuery()
        ;
        $q->setHydrate(false);
        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
            foreach ($result as $targetObject) {
                $slug = preg_replace("@^{$target}@smi", $replacement.$pathSeparator, $targetObject[$slugField]);
                $dm
                    ->createQueryBuilder()
                    ->update($useObjectClass)
                    ->field($slugField)->set($slug)
                    ->field($meta->identifier)->equals($targetObject['_id'])
                    ->getQuery()
                    ->execute()
                ;
            }
        }
    }
}
