<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Event\Adapter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Doctrine event adapter for ORM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ORM extends BaseAdapterORM implements LoggableAdapter
{
    public function getDefaultLogEntryClass()
    {
        return LogEntry::class;
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function isPostInsertGenerator($meta)
    {
        return $meta->idGenerator->isPostInsertGenerator();
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function getNewVersion($meta, $object)
    {
        $em = $this->getObjectManager();
        $objectMeta = $em->getClassMetadata(get_class($object));
        $wrapper = new EntityWrapper($object, $em);
        $objectId = $wrapper->getIdentifier(false, true);

        $dql = "SELECT MAX(log.version) FROM {$meta->getName()} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';

        $q = $em->createQuery($dql);
        $q->setParameters([
            'objectId' => $objectId,
            'objectClass' => $objectMeta->getName(),
        ]);

        return $q->getSingleScalarResult() + 1;
    }
}
