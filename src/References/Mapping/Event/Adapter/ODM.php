<?php

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Doctrine event adapter for ODM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements ReferencesAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof DocumentManager) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof EntityManagerInterface) {
            if ($object instanceof ORMProxy) {
                $id = $om->getUnitOfWork()->getEntityIdentifier($object);
            } else {
                $meta = $om->getClassMetadata(get_class($object));
                $id = [];
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

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleReference($om, $class, $identifier)
    {
        $this->throwIfNotEntityManager($om);
        $meta = $om->getClassMetadata($class);

        if (!$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }

        return $om->getReference($class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function extractIdentifier($om, $object, $single = true)
    {
        $meta = $om->getClassMetadata(get_class($object));
        if ($object instanceof GhostObjectInterface) {
            $id = $om->getUnitOfWork()->getDocumentIdentifier($object);
        } else {
            $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);
        }

        if ($single || !$id) {
            return $id;
        }

        return [$meta->identifier => $id];
    }

    /**
     * Override so we don't get an exception. We want to allow this.
     */
    private function throwIfNotEntityManager(EntityManagerInterface $em)
    {
    }
}
