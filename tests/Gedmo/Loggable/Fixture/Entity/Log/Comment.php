<?php

namespace Gedmo\Tests\Loggable\Fixture\Entity\Log;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * @ORM\Table(name="test_comment_log_entries")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class Comment extends AbstractLogEntry
{
}
