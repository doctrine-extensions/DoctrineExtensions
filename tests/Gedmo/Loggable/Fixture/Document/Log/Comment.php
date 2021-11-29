<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document\Log;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Loggable\Document\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\Document\Repository\LogEntryRepository;

/**
 * @ODM\Document(
 *     collection="test_comment_log_entries",
 *     repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository"
 * )
 */
#[ODM\Document(collection: 'test_comment_log_entries', repositoryClass: LogEntryRepository::class)]
class Comment extends AbstractLogEntry
{
}
