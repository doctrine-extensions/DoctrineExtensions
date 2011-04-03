<?php

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Cursor;

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
final class ODM extends BaseAdapterODM
{
    /**
     * Loads the similar slugs
     *
     * @param DocumentManager $dm
     * @param object $object
     * @param ClassMetadataInfo $meta
     * @param array $config
     * @param string $slug
     * @return array
     */
    public function getSimilarSlugs(DocumentManager $dm, $object, ClassMetadataInfo $meta, array $config, $slug)
    {
        $qb = $dm->createQueryBuilder($meta->name);
        $identifier = $this->extractIdentifier($dm, $object);
        if ($identifier) {
            $qb->field($meta->identifier)->notEqual($identifier);
        }
        $qb->where("function() {
            return this.{$config['slug']}.indexOf('{$slug}') === 0;
        }");
        $q = $qb->getQuery();
        $q->setHydrate(false);

        $result = $q->execute();
        if ($result instanceof Cursor) {
            $result = $result->toArray();
        }
        return $result;
    }
}