<?php

namespace Gedmo\Tree\Strategy\ODM\MongoDB;

use Gedmo\Tree\Strategy\AbstractMaterializedPath;

/**
 * This strategy makes tree using materialized path strategy
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy.ODM.MongoDB
 * @subpackage MaterializedPath
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPath extends AbstractMaterializedPath
{
    /**
     * {@inheritdoc}
     */
    public function removeNode($om, $meta, $config, $node)
    {
        $uow = $om->getUnitOfWork();
        $pathProp = $meta->getReflectionProperty($config['path']);
        $pathProp->setAccessible(true);

        // Remove node's children
        $results = $om->createQueryBuilder()
            ->find($meta->name)
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
    public function getChildren($om, $meta, $config, $originalPath)
    {
        return $om->createQueryBuilder()
            ->find($meta->name)
            ->field($config['path'])->equals(new \MongoRegex('/^'.preg_quote($originalPath).'.+/'))
            ->sort($config['path'], 'asc')      // This may save some calls to updateNode
            ->getQuery()
            ->execute();
    }
}
