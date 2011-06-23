<?php

namespace Loggable\Fixture\Entity\Log;

use Gedmo\Loggable\Entity\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_comment_log_entries")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class Comment extends AbstractLogEntry
{

}