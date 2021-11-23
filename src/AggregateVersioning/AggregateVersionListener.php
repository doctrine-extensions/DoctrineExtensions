<?php

declare(strict_types=1);

namespace Gedmo\AggregateVersioning;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Gedmo\Mapping\Annotation\AggregateVersioning;
use Gedmo\Mapping\MappedEventSubscriber;
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

        if (empty($entities)) {
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
        foreach ($entities as $entity) {
            if ($entity instanceof AggregateRoot) {
                return $entity;
            }
        }

        $entity = end($entities);
        $annotation = $this->getAggregateEntityAnnotation($entity);

        if (!method_exists($entity, $annotation->aggregateRootMethod)) {
            throw new LogicException(sprintf('Method "%s" not exists in class "%s".', $annotation->aggregateRootMethod, get_class($entity)));
        }

        return $entity->{$annotation->aggregateRootMethod}();
    }

    private function getAggregateEntityAnnotation(AggregateEntity $entity): AggregateVersioning
    {
        $reflectionClass = new ReflectionClass($entity);

        $reader = new AnnotationReader();

        if (!$annotation = $reader->getClassAnnotation($reflectionClass, AggregateVersioning::class)) {
            throw new LogicException(sprintf('Aggregate "%s" must have %s.', get_class($entity), AggregateVersioning::class));
        }

        return $annotation;
    }
}
