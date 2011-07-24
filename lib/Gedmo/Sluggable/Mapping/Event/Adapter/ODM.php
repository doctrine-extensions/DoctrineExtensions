<?php

namespace Gedmo\Sluggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
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
    public function getSimilarSlugs($object, ClassMetadata $meta, array $config, $slug)
    {
        $dm = $this->getObjectManager();
        $qb = $dm->createQueryBuilder($config['useObjectClass']);
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

    /**
     * {@inheritDoc}
     */
    public function replaceRelative($object, array $config, $target, $replacement)
    {
        throw new \Exception('not implemented yet');
    }

    /**
     * {@inheritDoc}
     */
    public function replaceInverseRelative($object, array $config, $target, $replacement)
    {
        throw new \Exception('not implemented yet');
    }
}