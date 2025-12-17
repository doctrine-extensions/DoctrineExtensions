<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Revisionable\Document\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Document\Repository\RevisionRepository;
use Gedmo\Revisionable\Revisionable;

/**
 * Default concrete revision implementation for the Doctrine MongoDB ODM.
 *
 * @ODM\Document(repositoryClass="Gedmo\Revisionable\Document\Repository\RevisionRepository")
 * @ODM\Index(keys={"revisionableClass": "asc"})
 * @ODM\Index(keys={"loggedAt": "asc"})
 * @ODM\Index(keys={"username": "asc"})
 * @ODM\Index(keys={"revisionableId": "asc", "revisionableClass": "asc", "version": "asc"})
 *
 * @template T of Revisionable|object
 *
 * @template-extends AbstractRevision<T>
 */
#[ODM\Document(repositoryClass: RevisionRepository::class)]
#[ODM\Index(keys: ['revisionableClass' => 'asc'])]
#[ODM\Index(keys: ['loggedAt' => 'asc'])]
#[ODM\Index(keys: ['username' => 'asc'])]
#[ODM\Index(keys: ['revisionableId' => 'asc', 'revisionableClass' => 'asc', 'version' => 'asc'])]
class Revision extends AbstractRevision
{
    /**
     * Named constructor to create a new revision.
     *
     * Implementations should handle setting the initial logged at time and version for new instances within this constructor.
     *
     * @param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     *
     * @return self<T>
     */
    public static function createRevision(string $action): self
    {
        $document = new self();
        $document->setAction($action);
        $document->setLoggedAt(new \DateTimeImmutable());

        return $document;
    }
}
