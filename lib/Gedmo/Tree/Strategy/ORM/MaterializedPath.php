<?php

namespace Gedmo\Tree\Strategy\ORM;

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
        $path = addcslashes($pathProp->getValue($node), '%');

        // Remove node's children
        $qb = $om->createQueryBuilder();
        $qb->select('e')
            ->from($meta->rootEntityName, 'e')
            ->where($qb->expr()->like('e.'.$config['path'], $qb->expr()->literal($path.'%')));
        $results = $qb->getQuery()
            ->execute();

        foreach ($results as $node) {
            $uow->scheduleForDelete($node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(ObjectManager $om, ClassMetadata $meta, array $config, $path)
    {
        $path = addcslashes($path, '%');
        $qb = $om->createQueryBuilder($meta->rootEntityName);
        $qb->select('e')
            ->from($meta->rootEntityName, 'e')
            ->where($qb->expr()->like('e.'.$config['path'], $qb->expr()->literal($path.'%')))
            ->andWhere('e.'.$config['path'].' != :path')
            ->orderBy('e.'.$config['path'], 'asc');      // This may save some calls to updateNode
        $qb->setParameter('path', $path);

        return $qb->getQuery()
            ->execute();
    }
}
