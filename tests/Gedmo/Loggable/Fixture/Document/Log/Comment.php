<?php

namespace Gedmo\Tests\Loggable\Fixture\Document\Log;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Loggable\Document\MappedSuperclass\AbstractLogEntry;

/**
 * @ODM\Document(
 *     collection="test_comment_log_entries",
 *     repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository"
 * )
 */
class Comment extends AbstractLogEntry
{
}
