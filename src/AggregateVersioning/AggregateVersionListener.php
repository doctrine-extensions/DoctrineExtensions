<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\AggregateVersioning;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Gedmo\Mapping\Annotation\AggregateVersioning;
use Gedmo\Mapping\MappedEventSubscriber;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

/**
 * The Aggregate version listener handles the updated Aggregate Root and Aggregate Entity
 * and update Aggregate version
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class AggregateVersionListener extends MappedEventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            'onFlush',
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $all = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        $entities = [];
        foreach ($all as $entity) {
            if ($entity instanceof AggregateEntity || $entity instanceof AggregateRoot) {
                $entities[] = $entity;
            }
        }

        if ([] === $entities) {
            return;
        }

        $aggregateRoot = $this->getAggregateRoot($entities);
        $aggregateRoot->updateAggregateVersion();

        $meta = $em->getClassMetadata(get_class($aggregateRoot));
        $uow->recomputeSingleEntityChangeSet($meta, $aggregateRoot);
    }

    protected function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    private function getAggregateRoot(array $entities): AggregateRoot
    {
        if ([] === $entities) {
            throw new InvalidArgumentException('Expected a non-empty entities.');
        }

        foreach ($entities as $entity) {
            if ($entity instanceof AggregateRoot) {
                return $entity;
            }
        }

        $entity = end($entities);
        $method = $this->getAggregateRootMethod($entity);

        if (!method_exists($entity, $method)) {
            throw new LogicException(sprintf('Method "%s()" does not exist in class "%s".', $method, get_class($entity)));
        }

        return $entity->{$method}();
    }

    private function getAggregateRootMethod(AggregateEntity $entity): string
    {
        $reflection = new ReflectionClass($entity);

        if (\PHP_VERSION_ID >= 80000) {
            $attributes = $reflection->getAttributes(AggregateVersioning::class);
            if (!empty($attributes)) {
                $arguments = $attributes[0]->getArguments();
                if (!isset($arguments['aggregateRootMethod'])) {
                    throw new LogicException(sprintf('Attribute "%s" in entity "%s" must have argument "%s".', AggregateVersioning::class, get_class($entity), 'aggregateRootMethod'));
                }

                return $arguments['aggregateRootMethod'];
            }
        }

        $reader = new AnnotationReader();
        if ($annotation = $reader->getClassAnnotation($reflection, AggregateVersioning::class)) {
            return $annotation->aggregateRootMethod;
        }

        throw new LogicException(sprintf('Aggregate "%s" must have %s.', get_class($entity), AggregateVersioning::class));
    }
}
