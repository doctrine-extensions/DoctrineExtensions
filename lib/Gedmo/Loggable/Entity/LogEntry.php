<?php

namespace Gedmo\Loggable\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Entity;

/**
 * Gedmo\Loggable\Entity\LogEntry
 *
 * @Table(
 *     name="ext_log_entries",
 *  indexes={
 *      @index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @index(name="log_user_lookup_idx", columns={"username"}),
 *      @index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
