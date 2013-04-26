<?php

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy as MongoDBProxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;

/**
 * Doctrine event adapter for ORM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements ReferencesAdapter
{
    /**
     * @inheritDoc
     */
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof EntityManager) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof DocumentManager) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($object instanceof MongoDBProxy) {
                $id = $om->getUnitOfWork()->getDocumentIdentifier($object);
            } else {
                $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);
            }

            if ($single || !$id) {
                return $id;
            }

            return array($meta->identifier => $id);
        }
    }

    /**
     * @inheritDoc
     */
    public function getSingleReference($om, $class, $identifier)
    {
        $this->throwIfNotDocumentManager($om);
        $meta = $om->getClassMetadata($class);

        if (!$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }

        return $om->getReference($class, $identifier);
    }

    /**
     * @inheritDoc
     */
    public function extractIdentifier($om, $object, $single = true)
    {
        if ($object instanceof ORMProxy) {
            $id = $om->getUnitOfWork()->getEntityIdentifier($object);
        } else {
            $meta = $om->getClassMetadata(get_class($object));
            $id = array();
            foreach ($meta->identifier as $name) {
                $id[$name] = $meta->getReflectionProperty($name)->getValue($object);
                // return null if one of identifiers is missing
                if (!$id[$name]) {
                    return null;
                }
            }
        }

        if ($single) {
            $id = current($id);
        }

        return $id;
    }

    /**
     * Override so we don't get an exception. We want to allow this.
     */
    private function throwIfNotDocumentManager(DocumentManager $dm)
    {
    }
}
