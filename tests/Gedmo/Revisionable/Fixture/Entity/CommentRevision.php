<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Revisionable\Entity\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Entity\Repository\RevisionRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Revisionable\Entity\Repository\RevisionRepository")
 *
 * @template T of Comment
 *
 * @template-extends AbstractRevision<T>
 */
#[ORM\Entity(repositoryClass: RevisionRepository::class)]
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
        $entity = new self();
        $entity->setAction($action);
        $entity->setLoggedAt(new \DateTimeImmutable());

        return $entity;
    }
}
