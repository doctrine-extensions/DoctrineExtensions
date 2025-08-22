<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy as PersistenceProxy;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Doctrine event adapter for ODM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
final class ODM extends BaseAdapterODM implements ReferencesAdapter
{
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof DocumentManager) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof EntityManagerInterface) {
            if ($object instanceof PersistenceProxy) {
                $id = $om->getUnitOfWork()->getEntityIdentifier($object);
            } else {
                $meta = $om->getClassMetadata(get_class($object));
                $id = [];
                foreach ($meta->getIdentifier() as $name) {
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

        return null;
    }

    public function getSingleReference($om, $class, $identifier)
    {
        $meta = $om->getClassMetadata($class);

        if (!$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }

        return $om->getReference($class, $identifier);
    }

    public function extractIdentifier($om, $object, $single = true)
    {
        $meta = $om->getClassMetadata(get_class($object));
        if ($object instanceof GhostObjectInterface) {
            $id = $om->getUnitOfWork()->getDocumentIdentifier($object);
        } else {
            $id = $meta->getReflectionProperty($meta->getIdentifier()[0])->getValue($object);
        }

        if ($single || !$id) {
            return $id;
        }

        return [$meta->getIdentifier()[0] => $id];
    }
}
