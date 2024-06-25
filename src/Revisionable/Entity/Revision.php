<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Revisionable\Entity\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Entity\Repository\RevisionRepository;
use Gedmo\Revisionable\Revisionable;

/**
 * Default concrete revision implementation for the Doctrine ORM.
 *
 * @ORM\Table(
 *     name="revisions",
 *     options={"row_format": "DYNAMIC"},
 *     indexes={
 *         @ORM\Index(name="revision_class_lookup_idx", columns={"revisionable_class"}),
 *         @ORM\Index(name="revision_date_lookup_idx", columns={"logged_at"}),
 *         @ORM\Index(name="revision_user_lookup_idx", columns={"username"}),
 *         @ORM\Index(name="revision_version_lookup_idx", columns={"revisionable_id", "revisionable_class", "version"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Revisionable\Entity\Repository\RevisionRepository")
 *
 * @phpstan-template T of Revisionable|object
 *
 * @phpstan-extends AbstractRevision<T>
 */
#[ORM\Entity(repositoryClass: RevisionRepository::class)]
#[ORM\Table(name: 'revisions', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(name: 'revision_class_lookup_idx', columns: ['revisionable_class'])]
#[ORM\Index(name: 'revision_date_lookup_idx', columns: ['logged_at'])]
#[ORM\Index(name: 'revision_user_lookup_idx', columns: ['username'])]
#[ORM\Index(name: 'revision_version_lookup_idx', columns: ['revisionable_id', 'revisionable_class', 'version'])]
class Revision extends AbstractRevision
{
    /**
     * Named constructor to create a new revision.
     *
     * Implementations should handle setting the initial logged at time and version for new instances within this constructor.
     *
     * @phpstan-return self<T>
     */
    public static function createRevision(): self
    {
        $entity = new self();
        $entity->loggedAt = new \DateTimeImmutable();

        return $entity;
    }
}
