<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Event\Adapter;

use Gedmo\Loggable\Document\LogEntry;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;

/**
 * Doctrine event adapter for ODM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ODM extends BaseAdapterODM implements LoggableAdapter
{
    public function getDefaultLogEntryClass()
    {
        return LogEntry::class;
    }

    public function isPostInsertGenerator($meta)
    {
        return false;
    }

    public function getNewVersion($meta, $object)
    {
        $dm = $this->getObjectManager();
        $objectMeta = $dm->getClassMetadata(get_class($object));
        $identifierField = $this->getSingleIdentifierFieldName($objectMeta);
        $objectId = $objectMeta->getReflectionProperty($identifierField)->getValue($object);

        $qb = $dm->createQueryBuilder($meta->getName());
        $qb->select('version');
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->getName());
        $qb->sort('version', 'DESC');
        $qb->limit(1);
        $q = $qb->getQuery();
        $q->setHydrate(false);

        $result = $q->getSingleResult();
        if ($result) {
            $result = $result['version'] + 1;
        }

        return $result;
    }
}
