<?php

namespace Gedmo\Loggable\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gedmo\Loggable\Entity\LogEntry
 *
 * @ORM\Table(name="ext_log_entries")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
