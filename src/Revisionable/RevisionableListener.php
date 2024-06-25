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
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Revisionable\Mapping\Event\RevisionableAdapter;
use Gedmo\Tool\ActorProviderInterface;
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
 *   versioned?: list<non-empty-string>,
 * }
 *
 * @template T of Revisionable|object
 *
 * @phpstan-extends MappedEventSubscriber<RevisionableConfiguration, RevisionableAdapter>
 */
final class RevisionableListener extends MappedEventSubscriber
{
    private ?ActorProviderInterface $actorProvider = null;

    /**
     * Username for identification
     *
     * @var non-empty-string|null
     */
    private ?string $username = null;

    /**
     * List of revisions which do not have the foreign key generated yet - MySQL case.
     *
     * These entries will be updated with new keys on postPersist event
     *
     * @var array<int, RevisionInterface<T>>
     */
    private array $pendingRevisionInserts = [];

    /**
     * For log of changed relations we use its identifiers to avoid storing serialized Proxies.
     *
     * These are pending relations in case it does not have an identifier yet.
     *
     * @var array<int, array<int, array{revision: RevisionInterface<T>, field: string}>>
     */
    private array $pendingRelatedObjects = [];

    /**
     * Set an actor provider for the user value.
     */
    public function setActorProvider(ActorProviderInterface $actorProvider): void
    {
        $this->actorProvider = $actorProvider;
    }

    /**
     * Set the username to be used when logging revisions.
     *
     * If an actor provider is also provided, it will take precedence over this value.
     *
     * @param non-empty-string|object $username
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
     * @param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     */
    public function loadClassMetadata(EventArgs $eventArgs): void
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for inserted objects to update the revision's foreign key.
     *
     * @param LifecycleEventArgs<ObjectManager> $args
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
            $revisionMeta->setFieldValue($revision, 'revisionableId', $id);
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
     * @param ManagerEventArgs<ObjectManager> $eventArgs
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

    protected function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * Get the {@see RevisionInterface} class name to use when creating revisions for the provided class.
     *
     * @param class-string $class
     *
     * @return class-string<RevisionInterface<T>>
     */
    private function getRevisionClass(RevisionableAdapter $ea, string $class): string
    {
        return $this->getConfiguration($ea->getObjectManager(), $class)['revisionClass'] ?? $ea->getDefaultRevisionClass();
    }

    /**
     * Retrieve the username to use for the log entry.
     *
     * This method will try to fetch a username from the actor provider first, falling back to the {@see $this->username}
     * property if the provider is not set or does not provide a value.
     *
     * @throws UnexpectedValueException if the actor provider provides an unsupported username value
     *
     * @return non-empty-string|null
     */
    private function getUsername(): ?string
    {
        if ($this->actorProvider instanceof ActorProviderInterface) {
            $actor = $this->actorProvider->getActor();

            if (is_string($actor) || null === $actor) {
                return $actor;
            }

            if (method_exists($actor, 'getUserIdentifier')) {
                return (string) $actor->getUserIdentifier();
            }

            if (method_exists($actor, 'getUsername')) {
                return (string) $actor->getUsername();
            }

            if (method_exists($actor, '__toString')) {
                return $actor->__toString();
            }

            throw new UnexpectedValueException(\sprintf('The revisionable extension requires the actor provider to return a string or an object implementing the "getUserIdentifier()", "getUsername()", or "__toString()" methods. "%s" cannot be used as an actor.', get_class($actor)));
        }

        return $this->username;
    }

    /**
     * Provides the changed data for an object to store in a revision.
     *
     * @param T                    $object
     * @param RevisionInterface<T> $revision
     *
     * @return array<string, mixed>
     */
    private function getObjectChangeSetData(RevisionableAdapter $ea, object $object, RevisionInterface $revision): array
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

            if ($meta instanceof MongoDBODMClassMetadata && $meta->hasEmbed($field)) {
                $value = $this->getObjectChangeSetData($ea, $value, $revision);
            } elseif ($meta->isSingleValuedAssociation($field)) {
                if ($value) {
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
     * @param RevisionInterface::ACTION_CREATE|RevisionInterface::ACTION_UPDATE|RevisionInterface::ACTION_REMOVE $action
     * @param T                                                                                                  $object
     *
     * @return RevisionInterface<T>|null
     */
    private function createRevision(string $action, object $object, RevisionableAdapter $ea): ?RevisionInterface
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

        $revision = $revisionClass::createRevision($action);
        $revision->setUsername($this->getUsername());
        $revision->setRevisionableClass($meta->getName());

        // check for the availability of the primary key
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

        $om->getUnitOfWork()->computeChangeSet($revisionMeta, $revision);

        return $revision;
    }
}
