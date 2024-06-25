<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBODMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Revisionable\Mapping\Event\RevisionableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Revisionable listener
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-type RevisionableConfiguration = array{
 *   revisionable?: bool,
 *   revisionClass?: class-string<RevisionInterface<T>>,
 *   versioned?: string[],
 * }
 *
 * @template T of Revisionable|object
 *
 * @phpstan-extends MappedEventSubscriber<RevisionableConfiguration, RevisionableAdapter>
 */
final class RevisionableListener extends MappedEventSubscriber
{
    /**
     * Username for identification
     *
     * @phpstan-var non-empty-string|null
     */
    protected ?string $username = null;

    /**
     * List of revisions which do not have the foreign key generated yet - MySQL case.
     *
     * These entries will be updated with new keys on postPersist event
     *
     * @var array<int, RevisionInterface>
     *
     * @phpstan-var array<int, RevisionInterface<T>>
     */
    protected array $pendingRevisionInserts = [];

    /**
     * For log of changed relations we use its identifiers to avoid storing serialized Proxies.
     *
     * These are pending relations in case it does not have an identifier yet.
     *
     * @var array<int, array<int, array<string, RevisionInterface|string>>>
     *
     * @phpstan-var array<int, array<int, array{revision: RevisionInterface<T>, field: string}>>
     */
    protected array $pendingRelatedObjects = [];

    /**
     * Set the username to be used when logging revisions.
     *
     * @param string|object $username
     *
     * @phpstan-param non-empty-string|object $username
     *
     * @throws InvalidArgumentException Invalid username
     */
    public function setUsername($username): void
    {
        if (is_string($username)) {
            $this->username = $username;

            return;
        }

        if (!is_object($username)) {
            throw new InvalidArgumentException('The username must be a string or an object implementing Stringable or with a getUserIdentifier or getUsername method.');
        }

        if (method_exists($username, 'getUserIdentifier')) {
            $this->username = (string) $username->getUserIdentifier();

            return;
        }

        if (method_exists($username, 'getUsername')) {
            $this->username = (string) $username->getUsername();

            return;
        }

        if (method_exists($username, '__toString')) {
            $this->username = $username->__toString();

            return;
        }

        throw new InvalidArgumentException('The username must be a string or an object implementing Stringable or with a getUserIdentifier or getUsername method.');
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            'onFlush',
            'loadClassMetadata',
            'postPersist',
        ];
    }

    /**
     * Maps additional metadata for revisionable objects.
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     */
    public function loadClassMetadata(EventArgs $eventArgs): void
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for inserted objects to update the revision's foreign key.
     *
     * @param LifecycleEventArgs $args
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $args
     */
    public function postPersist(EventArgs $args): void
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_id($object);
        $uow = $om->getUnitOfWork();

        if ($this->pendingRevisionInserts && array_key_exists($oid, $this->pendingRevisionInserts)) {
            $wrapped = AbstractWrapper::wrap($object, $om);

            $revision = $this->pendingRevisionInserts[$oid];
            $revisionMeta = $om->getClassMetadata(get_class($revision));

            $id = $wrapped->getIdentifier(false, true);
            $revisionMeta->getReflectionProperty('revisionableId')->setValue($revision, $id);
            $uow->scheduleExtraUpdate($revision, [
                'revisionableId' => [null, $id],
            ]);
            $ea->setOriginalObjectProperty($uow, $revision, 'revisionableId', $id);
            unset($this->pendingRevisionInserts[$oid]);
        }

        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            $wrapped = AbstractWrapper::wrap($object, $om);
            $identifiers = $wrapped->getIdentifier(false);

            foreach ($this->pendingRelatedObjects[$oid] as $props) {
                $revision = $props['revision'];

                $oldData = $data = $revision->getData();
                $data[$props['field']] = $identifiers;

                $revision->setData($data);

                $uow->scheduleExtraUpdate($revision, [
                    'data' => [$oldData, $data],
                ]);
                $ea->setOriginalObjectProperty($uow, $revision, 'data', $data);
            }
            unset($this->pendingRelatedObjects[$oid]);
        }
    }

    /**
     * Creates revisions for revisionable objects.
     *
     * @param ManagerEventArgs $eventArgs
     *
     * @phpstan-param ManagerEventArgs<ObjectManager> $eventArgs
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->createRevision(RevisionInterface::ACTION_CREATE, $object, $ea);
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $this->createRevision(RevisionInterface::ACTION_UPDATE, $object, $ea);
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $this->createRevision(RevisionInterface::ACTION_REMOVE, $object, $ea);
        }
    }

    /**
     * Get the {@see RevisionInterface} class name to use when creating revisions for the provided class.
     *
     * @phpstan-param class-string $class
     *
     * @phpstan-return class-string<RevisionInterface<T>>
     */
    protected function getRevisionClass(RevisionableAdapter $ea, string $class): string
    {
        return $this->getConfiguration($ea->getObjectManager(), $class)['revisionClass'] ?? $ea->getDefaultRevisionClass();
    }

    protected function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * Returns an object's changeset data
     *
     * @return array<string, mixed>
     *
     * @phpstan-param T $object
     * @phpstan-param RevisionInterface<T> $revision
     */
    protected function getObjectChangeSetData(RevisionableAdapter $ea, object $object, RevisionInterface $revision): array
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->getName());
        $uow = $om->getUnitOfWork();
        $newValues = [];

        foreach ($ea->getObjectChangeSet($uow, $object) as $field => $changes) {
            if (empty($config['versioned']) || !in_array($field, $config['versioned'], true)) {
                continue;
            }

            $value = $changes[1];

            if ($meta->isSingleValuedAssociation($field)) {
                if ($value) {
                    if ($wrapped->isEmbeddedAssociation($field)) {
                        $value = $this->getObjectChangeSetData($ea, $value, $revision);
                    } else {
                        $oid = spl_object_id($value);
                        $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                        $value = $wrappedAssoc->getIdentifier(false);

                        if (!is_array($value) && !$value) {
                            $this->pendingRelatedObjects[$oid][] = [
                                'revision' => $revision,
                                'field' => $field,
                            ];
                        }
                    }
                }
            } else {
                $value = $wrapped->convertToDatabaseValue($value, $meta->getTypeOfField($field));
            }

            $newValues[$field] = $value;
        }

        return $newValues;
    }

    /**
     * Create a new {@see RevisionInterface} instance
     *
     * @phpstan-param RevisionInterface::ACTION_CREATE|RevisionInterface::ACTION_UPDATE|RevisionInterface::ACTION_REMOVE $action
     * @phpstan-param T $object
     *
     * @phpstan-return RevisionInterface<T>|null
     */
    protected function createRevision(string $action, object $object, RevisionableAdapter $ea): ?RevisionInterface
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();

        // Exclude embedded documents
        if ($meta instanceof MongoDBODMClassMetadata && $meta->isEmbeddedDocument) {
            return null;
        }

        $config = $this->getConfiguration($om, $meta->getName());

        if ([] === $config) {
            return null;
        }

        $revisionClass = $this->getRevisionClass($ea, $meta->getName());
        $revisionMeta = $om->getClassMetadata($revisionClass);

        $revision = $revisionClass::createRevision();
        $revision->setAction($action);
        $revision->setUsername($this->username);
        $revision->setRevisionableClass($meta->getName());

        // check for the availability of the primary key
        $uow = $om->getUnitOfWork();

        if (RevisionInterface::ACTION_CREATE === $action && ($ea->isPostInsertGenerator($meta) || ($meta instanceof ORMClassMetadata && $meta->isIdentifierComposite))) {
            $this->pendingRevisionInserts[spl_object_id($object)] = $revision;
        } else {
            $revision->setRevisionableId($wrapped->getIdentifier(false, true));
        }

        $newValues = [];

        if (RevisionInterface::ACTION_REMOVE !== $action && isset($config['versioned'])) {
            $newValues = $this->getObjectChangeSetData($ea, $object, $revision);
            $revision->setData($newValues);
        }

        // Don't create a revision if there's nothing to log on update
        if (RevisionInterface::ACTION_UPDATE === $action && [] === $newValues) {
            return null;
        }

        $version = 1;

        if (RevisionInterface::ACTION_CREATE !== $action) {
            $version = $ea->getNewVersion($revisionMeta, $object);

            if (empty($version)) {
                // was versioned later
                $version = 1;
            }
        }

        $revision->setVersion($version);

        $om->persist($revision);
        $uow->computeChangeSet($revisionMeta, $revision);

        return $revision;
    }
}
