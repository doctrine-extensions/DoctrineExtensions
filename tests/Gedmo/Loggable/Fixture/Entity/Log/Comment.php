<?php

namespace Loggable\Fixture\Entity\Log;

use Gedmo\Loggable\Entity\AbstractLogEntry;

/**
 * @Table(name="test_comment_log_entries")
 * @Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class Comment extends AbstractLogEntry
{
    
}