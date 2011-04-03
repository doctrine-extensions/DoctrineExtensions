<?php

namespace Gedmo\Loggable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Doctrine event adapter for ODM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM
{
    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    public function getDefaultLogEntryClass()
    {
        return 'Gedmo\\Loggable\\Document\\LogEntry';
    }

    /**
     * Get new version number
     *
     * @param ClassMetadataInfo $meta
     * @param DocumentManager $dm
     * @param object $object
     * @return integer
     */
    public function getNewVersion(ClassMetadataInfo $meta, DocumentManager $dm, $object)
    {
        $objectMeta = $dm->getClassMetadata(get_class($object));
        $identifierField = $this->getSingleIdentifierFieldName($objectMeta);
        $objectId = $objectMeta->getReflectionProperty($identifierField)->getValue($object);

        $qb = $dm->createQueryBuilder($meta->name);
        $qb->select('version');
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->name);
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