<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager as MongoDocumentManager;
use Doctrine\ODM\PHPCR\DocumentManager as PhpcrDocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Doctrine event adapter for ORM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
final class ORM extends BaseAdapterORM implements ReferencesAdapter
{
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof EntityManagerInterface) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof MongoDocumentManager) {
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

        if ($om instanceof PhpcrDocumentManager) {
            $meta = $om->getClassMetadata(get_class($object));
            assert(1 === count($meta->getIdentifier()));
            $id = $meta->getReflectionProperty($meta->getIdentifier()[0])->getValue($object);

            if ($single || !$id) {
                return $id;
            }

            return [$meta->getIdentifier()[0] => $id];
        }

        return null;
    }

    public function getSingleReference($om, $class, $identifier)
    {
        $this->throwIfNotDocumentManager($om);
        $meta = $om->getClassMetadata($class);

        if ($om instanceof MongoDocumentManager) {
            if (!$meta->isInheritanceTypeNone()) {
                return $om->find($class, $identifier);
            }
        }

        return $om->getReference($class, $identifier);
    }

    public function extractIdentifier($om, $object, $single = true)
    {
        if ($object instanceof ORMProxy) {
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

    /**
     * Override so we don't get an exception. We want to allow this.
     *
     * @param MongoDocumentManager|PhpcrDocumentManager $dm
     */
    private function throwIfNotDocumentManager($dm): void
    {
        if (!($dm instanceof MongoDocumentManager) && !($dm instanceof PhpcrDocumentManager)) {
            throw new InvalidArgumentException(sprintf('Expected a %s or %s instance but got "%s"', MongoDocumentManager::class, 'Doctrine\ODM\PHPCR\DocumentManager', is_object($dm) ? get_class($dm) : gettype($dm)));
        }
    }
}
