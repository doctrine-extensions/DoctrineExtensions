<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Loggable\Document\Repository\LogEntryRepository;

/**
 * Gedmo\Loggable\Document\LogEntry
 *
 * @MongoODM\Document(repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository")
 * @MongoODM\Index(keys={"objectId": "asc", "objectClass": "asc", "version": "asc"})
 * @MongoODM\Index(keys={"loggedAt": "asc"})
 * @MongoODM\Index(keys={"objectClass": "asc"})
 * @MongoODM\Index(keys={"username": "asc"})
 */
#[MongoODM\Document(repositoryClass: LogEntryRepository::class)]
#[MongoODM\Index(keys: ['objectId' => 'asc', 'objectClass' => 'asc', 'version' => 'asc'])]
#[MongoODM\Index(keys: ['loggedAt' => 'asc'])]
#[MongoODM\Index(keys: ['objectClass' => 'asc'])]
#[MongoODM\Index(keys: ['username' => 'asc'])]
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
