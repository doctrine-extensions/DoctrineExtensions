<?php

namespace Loggable\Fixture\Document\Log;

use Gedmo\Loggable\Document\AbstractLogEntry;

/**
 * @Document(
 *     collection="test_comment_log_entries", 
 *     repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository"
 * )
 */
class Comment extends AbstractLogEntry
{
    
}