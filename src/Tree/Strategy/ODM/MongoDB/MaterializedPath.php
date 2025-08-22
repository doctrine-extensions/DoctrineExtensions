<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Strategy\ODM\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Strategy\AbstractMaterializedPath;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

/**
 * This strategy makes tree using materialized path strategy
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class MaterializedPath extends AbstractMaterializedPath
{
    /**
     * @param DocumentManager       $om
     * @param ClassMetadata<object> $meta
     */
    public function removeNode($om, $meta, $config, $node)
    {
        $uow = $om->getUnitOfWork();
        $wrapped = AbstractWrapper::wrap($node, $om);

        // Remove node's children
        $results = $om->createQueryBuilder()
            ->find($meta->getName())
            ->field($config['path'])->equals(new Regex('^'.preg_quote($wrapped->getPropertyValue($config['path'])).'.?+'))
            ->getQuery()
            ->getIterator();

        foreach ($results as $node) {
            $uow->scheduleForDelete($node);
        }
    }

    /**
     * @param DocumentManager       $om
     * @param ClassMetadata<object> $meta
     */
    public function getChildren($om, $meta, $config, $originalPath)
    {
        return $om->createQueryBuilder()
            ->find($meta->getName())
            ->field($config['path'])->equals(new Regex('^'.preg_quote($originalPath).'.+'))
            ->sort($config['path'], 'asc')      // This may save some calls to updateNode
            ->getQuery()
            ->getIterator();
    }

    /**
     * @param DocumentManager $om
     */
    protected function lockTrees(ObjectManager $om, AdapterInterface $ea)
    {
        $uow = $om->getUnitOfWork();

        foreach ($this->rootsOfTreesWhichNeedsLocking as $root) {
            $meta = $om->getClassMetadata(get_class($root));
            $config = $this->listener->getConfiguration($om, $meta->getName());
            $lockTimeProp = $meta->getReflectionProperty($config['lock_time']);
            $lockTimeProp->setAccessible(true);
            $lockTimeValue = new UTCDateTime();
            $lockTimeProp->setValue($root, $lockTimeValue);

            $ea->recomputeSingleObjectChangeSet($uow, $meta, $root);
        }
    }

    /**
     * @param DocumentManager $om
     */
    protected function releaseTreeLocks(ObjectManager $om, AdapterInterface $ea)
    {
        $uow = $om->getUnitOfWork();

        foreach ($this->rootsOfTreesWhichNeedsLocking as $oid => $root) {
            $meta = $om->getClassMetadata(get_class($root));
            $config = $this->listener->getConfiguration($om, $meta->getName());
            $lockTimeProp = $meta->getReflectionProperty($config['lock_time']);
            $lockTimeProp->setAccessible(true);
            $lockTimeValue = null;
            $lockTimeProp->setValue($root, $lockTimeValue);

            $ea->recomputeSingleObjectChangeSet($uow, $meta, $root);

            unset($this->rootsOfTreesWhichNeedsLocking[$oid]);
        }
    }
}
