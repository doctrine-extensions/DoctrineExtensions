<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Event\Adapter;

use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Revisionable\Entity\Revision;
use Gedmo\Revisionable\Mapping\Event\RevisionableAdapter;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionInterface;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Doctrine event adapter for the Revisionable extension when using the Doctrine ORM.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ORM extends BaseAdapterORM implements RevisionableAdapter
{
    /**
     * Get the default object class name used to store revisions.
     *
     * @phpstan-return class-string<RevisionInterface<Revisionable|object>>
     */
    public function getDefaultRevisionClass(): string
    {
        return Revision::class;
    }

    /**
     * Checks whether an identifier should be generated post insert.
     */
    public function isPostInsertGenerator(ClassMetadata $meta): bool
    {
        assert($meta instanceof ORMClassMetadata);

        return $meta->idGenerator->isPostInsertGenerator();
    }

    /**
     * Get the new version number for an object.
     *
     * @phpstan-return positive-int
     */
    public function getNewVersion(ClassMetadata $meta, object $object): int
    {
        assert($meta instanceof ORMClassMetadata);

        $em = $this->getObjectManager();

        $entityWrapper = new EntityWrapper($object, $em);

        $entityMetadata = $entityWrapper->getMetadata();
        $entityId = (string) $entityWrapper->getIdentifier(false, true);
        $entityClass = $entityMetadata->getName();

        $qb = $em->createQueryBuilder()
            ->select('MAX(revision.version)')
            ->from($meta->getName(), 'revision')
            ->where('revision.revisionableId = :revisionableId')
            ->andWhere('revision.revisionableClass = :revisionableClass')
            ->setParameter('revisionableId', $entityId)
            ->setParameter('revisionableClass', $entityClass);

        $version = (int) $qb->getQuery()->getSingleScalarResult();

        return $version + 1;
    }
}
