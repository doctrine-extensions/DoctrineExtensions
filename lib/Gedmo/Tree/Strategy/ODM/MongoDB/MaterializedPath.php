<?php

namespace Gedmo\Tree\Strategy\ODM\MongoDB;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Tree\Strategy\AbstractMaterializedPath;

/**
 * This strategy makes tree using materialized path strategy
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPath extends AbstractMaterializedPath
{
    /**
     * {@inheritdoc}
     */
    public function removeNode(ObjectManager $om, ClassMetadata $meta, array $config, $node)
    {
        $uow = $om->getUnitOfWork();
        $pathProp = $meta->getReflectionProperty($config['path']);

        // Remove node's children
        $results = $om->createQueryBuilder()
            ->find($meta->rootDocumentName)
            ->field($config['path'])->equals(new \MongoRegex('/^'.preg_quote($pathProp->getValue($node)).'.?+/'))
            ->getQuery()
            ->execute();

        foreach ($results as $node) {
            $uow->scheduleForDelete($node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(ObjectManager $om, ClassMetadata $meta, array $config, $originalPath)
    {
        return $om->createQueryBuilder()
            ->find($meta->rootDocumentName)
            ->field($config['path'])->equals(new \MongoRegex('/^'.preg_quote($originalPath).'.+/'))
            ->sort($config['path'], 'asc')      // This may save some calls to updateNode
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritedDoc}
     */
    protected function lockTrees(ObjectManager $om)
    {
        $uow = $om->getUnitOfWork();

        foreach ($this->rootsOfTreesWhichNeedsLocking as $oid => $root) {
            $meta = $om->getClassMetadata(get_class($root));
            $config = $this->listener->getConfiguration($om, $meta->name);
            $lockTimeProp = $meta->getReflectionProperty($config['lock_time']);
            $lockTimeValue = new \MongoDate();
            $lockTimeProp->setValue($root, $lockTimeValue);
            $changes = array(
                $config['lock_time'] => array(null, $lockTimeValue)
            );

            $uow->scheduleExtraUpdate($root, $changes);
            $uow->setOriginalDocumentProperty($oid, $config['lock_time'], $lockTimeValue);
        }
    }

    /**
     * {@inheritedDoc}
     */
    protected function releaseTreeLocks(ObjectManager $om)
    {
        $uow = $om->getUnitOfWork();

        foreach ($this->rootsOfTreesWhichNeedsLocking as $oid => $root) {
            $meta = $om->getClassMetadata(get_class($root));
            $config = $this->listener->getConfiguration($om, $meta->name);
            $lockTimeProp = $meta->getReflectionProperty($config['lock_time']);
            $lockTimeValue = null;
            $lockTimeProp->setValue($root, $lockTimeValue);
            $changes = array(
                $config['lock_time'] => array(null, null)
            );

            $uow->scheduleExtraUpdate($root, $changes);
            $uow->setOriginalDocumentProperty($oid, $config['lock_time'], $lockTimeValue);
            unset($this->rootsOfTreesWhichNeedsLocking[$oid]);
        }
    }
}
