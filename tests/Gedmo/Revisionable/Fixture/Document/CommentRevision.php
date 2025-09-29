<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Revisionable\Document\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Document\Repository\RevisionRepository;

/**
 * @ODM\Document(
 *     collection="comment_revisions",
 *     repositoryClass="Gedmo\Revisionable\Document\Repository\RevisionRepository"
 * )
 *
 * @template T of Comment
 *
 * @template-extends AbstractRevision<T>
 */
#[ODM\Document(collection: 'comment_revisions', repositoryClass: RevisionRepository::class)]
class CommentRevision extends AbstractRevision
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
