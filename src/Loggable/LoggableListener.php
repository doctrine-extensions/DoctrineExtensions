<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tool\ActorProviderInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Loggable listener
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-type LoggableConfiguration = array{
 *   loggable?: bool,
 *   logEntryClass?: class-string<LogEntryInterface<T>>,
 *   useObjectClass?: class-string,
 *   versioned?: string[],
 * }
 *
 * @template T of Loggable|object
 *
 * @phpstan-extends MappedEventSubscriber<LoggableConfiguration, LoggableAdapter>
 */
class LoggableListener extends MappedEventSubscriber
{
    /**
     * @deprecated use `LogEntryInterface::ACTION_CREATE` instead
     */
    public const ACTION_CREATE = LogEntryInterface::ACTION_CREATE;

    /**
     * @deprecated use `LogEntryInterface::ACTION_UPDATE` instead
     */
    public const ACTION_UPDATE = LogEntryInterface::ACTION_UPDATE;

    /**
     * @deprecated use `LogEntryInterface::ACTION_REMOVE` instead
     */
    public const ACTION_REMOVE = LogEntryInterface::ACTION_REMOVE;

    protected ?ActorProviderInterface $actorProvider = null;

    /**
     * Username for identification
     *
     * @var string
     */
    protected $username;

    /**
     * List of log entries which do not have the foreign
     * key generated yet - MySQL case. These entries
     * will be updated with new keys on postPersist event
     *
     * @var array<int, LogEntryInterface>
     *
     * @phpstan-var array<int, LogEntryInterface<T>>
     */
    protected $pendingLogEntryInserts = [];

    /**
     * For log of changed relations we use
     * its identifiers to avoid storing serialized Proxies.
     * These are pending relations in case it does not
     * have an identifier yet
     *
     * @var array<int, array<int, array<string, LogEntryInterface|string>>>
     *
     * @phpstan-var array<int, array<int, array{log: LogEntryInterface<T>, field: string}>>
     */
    protected $pendingRelatedObjects = [];

    /**
     * Set an actor provider for the user value.
     */
    public function setActorProvider(ActorProviderInterface $actorProvider): void
    {
        $this->actorProvider = $actorProvider;
    }

    /**
     * Set username for identification
     *
     * If an actor provider is also provided, it will take precedence over this value.
     *
     * @param mixed $username
     *
     * @throws InvalidArgumentException Invalid username
     *
     * @return void
     */
    public function setUsername($username)
    {
        if (is_string($username)) {
            $this->username = $username;
        } elseif (is_object($username) && method_exists($username, 'getUserIdentifier')) {
            $this->username = (string) $username->getUserIdentifier();
        } elseif (is_object($username) && method_exists($username, 'getUsername')) {
            $this->username = (string) $username->getUsername();
        } elseif (is_object($username) && method_exists($username, '__toString')) {
            $this->username = $username->__toString();
        } else {
            throw new InvalidArgumentException('Username must be a string, or object should have method getUserIdentifier, getUsername or __toString');
        }
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'loadClassMetadata',
            'postPersist',
        ];
    }

    /**
     * Maps additional metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for inserted object to update its logEntry
     * foreign key
     *
     * @param LifecycleEventArgs $args
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $args
     *
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_id($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {
            $wrapped = AbstractWrapper::wrap($object, $om);

            $logEntry = $this->pendingLogEntryInserts[$oid];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));

            $id = $wrapped->getIdentifier(false, true);
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);
            $uow->scheduleExtraUpdate($logEntry, [
                'objectId' => [null, $id],
            ]);
            $ea->setOriginalObjectProperty($uow, $logEntry, 'objectId', $id);
            unset($this->pendingLogEntryInserts[$oid]);
        }
        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            $wrapped = AbstractWrapper::wrap($object, $om);
            $identifiers = $wrapped->getIdentifier(false);
            foreach ($this->pendingRelatedObjects[$oid] as $props) {
                $logEntry = $props['log'];
                $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
                $oldData = $data = $logEntry->getData();
                $data[$props['field']] = $identifiers;

                $logEntry->setData($data);

                $uow->scheduleExtraUpdate($logEntry, [
                    'data' => [$oldData, $data],
                ]);
                $ea->setOriginalObjectProperty($uow, $logEntry, 'data', $data);
            }
            unset($this->pendingRelatedObjects[$oid]);
        }
    }

    /**
     * Looks for loggable objects being inserted or updated
     * for further processing
     *
     * @param ManagerEventArgs $eventArgs
     *
     * @phpstan-param ManagerEventArgs<ObjectManager> $eventArgs
     *
     * @return void
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(LogEntryInterface::ACTION_CREATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $this->createLogEntry(LogEntryInterface::ACTION_UPDATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $this->createLogEntry(LogEntryInterface::ACTION_REMOVE, $object, $ea);
        }
    }

    /**
     * Get the LogEntry class
     *
     * @param string $class
     *
     * @phpstan-param class-string $class
     *
     * @return string
     *
     * @phpstan-return class-string<LogEntryInterface<T>>
     */
    protected function getLogEntryClass(LoggableAdapter $ea, $class)
    {
        return self::$configurations[$this->name][$class]['logEntryClass'] ?? $ea->getDefaultLogEntryClass();
    }

    /**
     * Retrieve the username to use for the log entry.
     *
     * This method will try to fetch a username from the actor provider first, falling back to the {@see $this->username}
     * property if the provider is not set or does not provide a value.
     *
     * @throws UnexpectedValueException if the actor provider provides an unsupported username value
     */
    protected function getUsername(): ?string
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

            throw new UnexpectedValueException(\sprintf('The loggable extension requires the actor provider to return a string or an object implementing the "getUserIdentifier()", "getUsername()", or "__toString()" methods. "%s" cannot be used as an actor.', get_class($actor)));
        }

        return $this->username;
    }

    /**
     * Handle any custom LogEntry functionality that needs to be performed
     * before persisting it
     *
     * @param LogEntryInterface $logEntry The LogEntry being persisted
     * @param object            $object   The object being Logged
     *
     * @return void
     *
     * @phpstan-param LogEntryInterface<T> $logEntry
     * @phpstan-param T $object
     */
    protected function prePersistLogEntry($logEntry, $object)
    {
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Returns an objects changeset data
     *
     * @param LoggableAdapter   $ea
     * @param object            $object
     * @param LogEntryInterface $logEntry
     *
     * @return array<string, mixed>
     *
     * @phpstan-param T $object
     * @phpstan-param LogEntryInterface<T> $logEntry
     */
    protected function getObjectChangeSetData($ea, $object, $logEntry)
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
            if ($meta->isSingleValuedAssociation($field) && $value) {
                if ($wrapped->isEmbeddedAssociation($field)) {
                    $value = $this->getObjectChangeSetData($ea, $value, $logEntry);
                } else {
                    $oid = spl_object_id($value);
                    $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                    $value = $wrappedAssoc->getIdentifier(false);
                    if (!is_array($value) && !$value) {
                        $this->pendingRelatedObjects[$oid][] = [
                            'log' => $logEntry,
                            'field' => $field,
                        ];
                    }
                }
            }
            $newValues[$field] = $value;
        }

        return $newValues;
    }

    /**
     * Create a new Log instance
     *
     * @param string $action
     * @param object $object
     *
     * @return LogEntryInterface|null
     *
     * @phpstan-param LogEntryInterface::ACTION_CREATE|LogEntryInterface::ACTION_UPDATE|LogEntryInterface::ACTION_REMOVE $action
     * @phpstan-param T $object
     *
     * @phpstan-return LogEntryInterface<T>|null
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();

        // Filter embedded documents
        if (isset($meta->isEmbeddedDocument) && $meta->isEmbeddedDocument) {
            return null;
        }

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            $logEntryClass = $this->getLogEntryClass($ea, $meta->getName());
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            /** @var LogEntryInterface<T> $logEntry */
            $logEntry = $logEntryMeta->newInstance();

            $logEntry->setAction($action);
            $logEntry->setUsername($this->getUsername());
            $logEntry->setObjectClass($meta->getName());
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            $uow = $om->getUnitOfWork();
            if (LogEntryInterface::ACTION_CREATE === $action && ($ea->isPostInsertGenerator($meta) || ($meta instanceof ORMClassMetadata && $meta->isIdentifierComposite))) {
                $this->pendingLogEntryInserts[spl_object_id($object)] = $logEntry;
            } else {
                $logEntry->setObjectId($wrapped->getIdentifier(false, true));
            }
            $newValues = [];
            if (LogEntryInterface::ACTION_REMOVE !== $action && isset($config['versioned'])) {
                $newValues = $this->getObjectChangeSetData($ea, $object, $logEntry);
                $logEntry->setData($newValues);
            }

            if (LogEntryInterface::ACTION_UPDATE === $action && [] === $newValues) {
                return null;
            }

            $version = 1;
            if (LogEntryInterface::ACTION_CREATE !== $action) {
                $version = $ea->getNewVersion($logEntryMeta, $object);
                if (empty($version)) {
                    // was versioned later
                    $version = 1;
                }
            }
            $logEntry->setVersion($version);

            $this->prePersistLogEntry($logEntry, $object);

            $om->persist($logEntry);
            $uow->computeChangeSet($logEntryMeta, $logEntry);

            return $logEntry;
        }

        return null;
    }
}
