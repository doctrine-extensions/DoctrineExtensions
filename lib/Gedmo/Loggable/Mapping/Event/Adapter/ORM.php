<?php

namespace Gedmo\Loggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Doctrine event adapter for ORM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM
{
    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    public function getDefaultLogEntryClass()
    {
        return 'Gedmo\\Loggable\\Entity\\LogEntry';
    }

    /**
     * Get new version number
     *
     * @param ClassMetadataInfo $meta
     * @param EntityManager $em
     * @param object $object
     * @return integer
     */
    public function getNewVersion(ClassMetadataInfo $meta, EntityManager $em, $object)
    {
        $objectMeta = $em->getClassMetadata(get_class($object));
        $identifierField = $this->getSingleIdentifierFieldName($objectMeta);
        $objectId = $objectMeta->getReflectionProperty($identifierField)->getValue($object);

        $dql = "SELECT MAX(log.version) FROM {$meta->name} log";
        $dql .= " WHERE log.objectId = :objectId";
        $dql .= " AND log.objectClass = :objectClass";

        $q = $em->createQuery($dql);
        $q->setParameters(array(
            'objectId' => $objectId,
            'objectClass' => $objectMeta->name
        ));
        return $q->getSingleScalarResult() + 1;
    }
}