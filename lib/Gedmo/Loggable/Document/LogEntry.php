<?php

namespace Gedmo\Loggable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;

/**
 * Gedmo\Loggable\Document\LogEntry
 *
 * @Document(repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository")
 */
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */
}