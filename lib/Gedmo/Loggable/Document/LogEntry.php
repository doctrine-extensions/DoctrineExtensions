<?php

namespace Gedmo\Loggable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Gedmo\Loggable\Document\LogEntry
 *
 * @MongoODM\Document(
 *     repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository",
 *     indexes={
 *         @MongoODM\Index(keys={"objectId"="asc", "objectClass"="asc", "version"="asc"}),
 *         @MongoODM\Index(keys={"loggedAt"="asc"}),
 *         @MongoODM\Index(keys={"objectClass"="asc"}),
 *         @MongoODM\Index(keys={"username"="asc"})
 *     }
 * )
 */
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
