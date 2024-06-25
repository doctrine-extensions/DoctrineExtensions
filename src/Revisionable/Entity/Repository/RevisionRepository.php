<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Revisionable\Entity\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The RevisionRepository has some useful functions to interact with revisions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-template T of Revisionable|object
 *
 * @phpstan-extends EntityRepository<AbstractRevision<T>>
 */
class RevisionRepository extends EntityRepository
{
    /**
     * The revisionable listener associated with the entity manager for the {@see AbstractRevision} entity.
     *
     * @var RevisionableListener|bool|null
     *
     * @phpstan-var RevisionableListener<T>|null|false
     */
    private $listener = false;

    /**
     * Loads all revisions for the given entity
     *
     * @return AbstractRevision[]
     *
     * @phpstan-param T $entity
     *
     * @phpstan-return list<AbstractRevision<T>>
     */
    public function getRevisions(object $entity): array
    {
        $entityWrapper = new EntityWrapper($entity, $this->getEntityManager());

        $entityId = (string) $entityWrapper->getIdentifier(false, true);
        $entityClass = $entityWrapper->getMetadata()->getName();

        return $this->createQueryBuilder('revision')
            ->where('revision.revisionableId = :revisionableId')
            ->andWhere('revision.revisionableClass = :revisionableClass')
            ->orderBy('revision.version', 'DESC')
            ->setParameter('revisionableId', $entityId)
            ->setParameter('revisionableClass', $entityClass)
            ->getQuery()
            ->getResult();
    }

    /**
     * Reverts the given entity to the requested version, restoring all versioned fields to the state of that revision.
     *
     * Callers to this method will need to persist and flush changes to the entity.
     *
     * @phpstan-param T $entity
     * @phpstan-param positive-int $version
     *
     * @throws UnexpectedValueException
     */
    public function revert(object $entity, int $version = 1): void
    {
        $entityWrapper = new EntityWrapper($entity, $this->getEntityManager());

        $entityMetadata = $entityWrapper->getMetadata();
        $entityId = (string) $entityWrapper->getIdentifier(false, true);
        $entityClass = $entityMetadata->getName();

        $qb = $this->createQueryBuilder('revision')
            ->where('revision.revisionableId = :revisionableId')
            ->andWhere('revision.revisionableClass = :revisionableClass')
            ->andWhere('revision.version <= :version')
            ->orderBy('revision.version', 'DESC')
            ->setParameter('revisionableId', $entityId)
            ->setParameter('revisionableClass', $entityClass)
            ->setParameter('version', $version);

        $config = $this->getListener()->getConfiguration($this->getEntityManager(), $entityClass);
        $fields = $config['versioned'];
        $filled = false;
        $revisionsFound = false;

        $revisions = $qb->getQuery()->toIterable();

        assert($revisions instanceof \Generator);

        while ((null !== $revision = $revisions->current()) && !$filled) {
            $revisionsFound = true;
            $revisions->next();
            if ($data = $revision->getData()) {
                foreach ($data as $field => $value) {
                    if (in_array($field, $fields, true)) {
                        $this->mapValue($entityMetadata, $field, $value);
                        $entityWrapper->setPropertyValue($field, $value);
                        unset($fields[array_search($field, $fields, true)]);
                    }
                }
            }

            $filled = [] === $fields;
        }

        if (!$revisionsFound) {
            throw new UnexpectedValueException(sprintf('Could not find any revisions for version %d of entity %s.', $version, $entityClass));
        }

        if (count($fields)) {
            throw new UnexpectedValueException(sprintf('Could not fully revert entity %s to version %d.', $entityClass, $version));
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @phpstan-param ClassMetadata<T> $objectMeta
     */
    protected function mapValue(ClassMetadata $objectMeta, string $field, &$value)
    {
        if (!$objectMeta->isSingleValuedAssociation($field)) {
            $value = $this->getEntityManager()->getConnection()->convertToPHPValue($value, $objectMeta->getTypeOfField($field));

            return;
        }

        $mapping = $objectMeta->getAssociationMapping($field);
        $value = $value ? $this->getEntityManager()->getReference($mapping['targetEntity'], $value) : null;
    }

    /**
     * Get the revisionable listener associated with the entity manager for the {@see AbstractRevision} entity.
     *
     * @throws RuntimeException if the listener is not found
     *
     * @phpstan-return RevisionableListener<T>
     */
    private function getListener(): RevisionableListener
    {
        if ($this->listener instanceof RevisionableListener) {
            return $this->listener;
        }

        if (false === $this->listener) {
            foreach ($this->getEntityManager()->getEventManager()->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof RevisionableListener) {
                        return $this->listener = $listener;
                    }
                }
            }

            $this->listener = null;
        }

        throw new RuntimeException('The revisionable listener was not registered to the entity manager.');
    }
}
