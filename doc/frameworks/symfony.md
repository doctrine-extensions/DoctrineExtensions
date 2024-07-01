# Integrate the Doctrine Extensions in Symfony

This guide will demonstrate how to integrate the Doctrine Extensions library into a Symfony application.

> [!TIP]
> We recommend using the [`StofDoctrineExtensionsBundle`](https://symfony.com/bundles/StofDoctrineExtensionsBundle/current/index.html) which handles this integration for you.

## Index

- [Getting Started](#getting-started)
- [Registering Extension Listeners](#registering-extension-listeners)
- [Registering Mapping Configuration](#registering-mapping-configuration)
- [Registering Filters](#registering-filters)
- [Configuring Extensions via Event Subscribers](#configuring-extensions-via-event-subscribers)

## Getting Started

Assuming you have already [created your Symfony application](https://symfony.com/doc/current/getting_started/index.html),
the next step will be to ensure you've installed this library and the Doctrine libraries you will need.

For Doctrine MongoDB ODM users, this Composer command will install all required dependencies:

```shell
composer require doctrine/mongodb-odm doctrine/mongodb-odm-bundle gedmo/doctrine-extensions
```

For Doctrine ORM users, this Composer command will install all required dependencies:

```shell
composer require doctrine/dbal doctrine/doctrine-bundle doctrine/orm gedmo/doctrine-extensions
```

## Registering Extension Listeners

At the heart of the Doctrine Extensions library are the listeners which enable each extension. The below example demonstrates
how to register and enable all listeners provided by this library.

### Extensions Compatible with all Managers

> [!NOTE]
> This example shows the configuration when using the ORM and `DoctrineBundle` with a single default entity manager. When using the MongoDB ODM and `DoctrineMongoDBBundle`, the tag name should be `doctrine_mongodb.odm.event_listener` instead of `doctrine.event_listener`. When using an application with multiple managers, a separate tag is needed with the `connection` attribute for each connection.

```yaml
services:
    # Attribute mapping driver for the Doctrine Extension listeners
    gedmo.mapping.driver.attribute:
        class: Gedmo\Mapping\Driver\AttributeReader

    # Gedmo Blameable Extension Listener
    gedmo.listener.blameable:
        class: Gedmo\Blameable\BlameableListener
        tags:
            - { name: doctrine.event_listener, event: 'prePersist' }
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo IP Traceable Extension Listener
    gedmo.listener.ip_traceable:
        class: Gedmo\IpTraceable\IpTraceableListener
        tags:
            - { name: doctrine.event_listener, event: 'prePersist' }
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo Loggable Extension Listener
    gedmo.listener.loggable:
        class: Gedmo\Loggable\LoggableListener
        tags:
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine.event_listener, event: 'postPersist' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo Sluggable Extension Listener
    gedmo.listener.sluggable:
        class: Gedmo\Sluggable\SluggableListener
        tags:
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine.event_listener, event: 'prePersist' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo Soft Deleteable Extension listener
    gedmo.listener.soft_deleteable:
        class: Gedmo\SoftDeleteable\SoftDeleteableListener
        tags:
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine.event_listener, event: 'onFlush' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
            # The `clock` service was introduced in Symfony 6.2; if using an older Symfony version, you can either comment this call or provide your own PSR-20 Clock implementation
            - [ setClock, [ '@clock' ] ]

    # Gedmo Sortable Extension listener
    gedmo.listener.sortable:
        class: Gedmo\Sortable\SortableListener
        tags:
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine.event_listener, event: 'prePersist' }
            - { name: doctrine.event_listener, event: 'postPersist' }
            - { name: doctrine.event_listener, event: 'preUpdate' }
            - { name: doctrine.event_listener, event: 'postRemove' }
            - { name: doctrine.event_listener, event: 'postFlush' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo Timestampable Extension Listener
    gedmo.listener.timestampable:
        class: Gedmo\Timestampable\TimestampableListener
        tags:
            - { name: doctrine.event_listener, event: 'prePersist' }
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
            # The `clock` service was introduced in Symfony 6.2; if using an older Symfony version, you can either comment this call or provide your own PSR-20 Clock implementation
            - [ setClock, [ '@clock' ] ]

    # Gedmo Translatable Extension Listener
    gedmo.listener.translatable:
        class: Gedmo\Translatable\TranslatableListener
        tags:
            - { name: doctrine.event_listener, event: 'postLoad' }
            - { name: doctrine.event_listener, event: 'postPersist' }
            - { name: doctrine.event_listener, event: 'preFlush' }
            - { name: doctrine.event_listener, event: 'onFlush' }
            - { name: doctrine.event_listener, event: 'loadClassMetadata' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
            # The Kernel's `locale` parameter is used to configure the default locale for the extension
            - [ setDefaultLocale, [ '%locale%' ] ]

    # Gedmo Tree Extension Listener
    gedmo.listener.tree:
        class: Gedmo\Tree\TreeListener
        tags:
            - { name: doctrine.event_listener, event: 'prePersist'}
            - { name: doctrine.event_listener, event: 'preUpdate'}
            - { name: doctrine.event_listener, event: 'preRemove'}
            - { name: doctrine.event_listener, event: 'onFlush'}
            - { name: doctrine.event_listener, event: 'loadClassMetadata'}
            - { name: doctrine.event_listener, event: 'postPersist'}
            - { name: doctrine.event_listener, event: 'postUpdate'}
            - { name: doctrine.event_listener, event: 'postRemove'}
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
```

### Extensions Compatible with MongoDB ODM Only

> [!NOTE]
> This example shows the configuration when using the MongoDB ODM and `DoctrineMongoDBBundle` with a single default document manager. When using an application with multiple managers, a separate tag is needed with the `connection` attribute for each connection.

```yaml
services:
    # Gedmo Reference Integrity Extension Listener
    gedmo.listener.reference_integrity:
        class: Gedmo\ReferenceIntegrity\ReferenceIntegrityListener
        tags:
            - { name: doctrine_mongodb.odm.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine_mongodb.odm.event_listener, event: 'preRemove' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]

    # Gedmo References Extension Listener
    gedmo.listener.references:
        class: Gedmo\References\ReferencesListener
        tags:
            - { name: doctrine_mongodb.odm.event_listener, event: 'postLoad' }
            - { name: doctrine_mongodb.odm.event_listener, event: 'loadClassMetadata' }
            - { name: doctrine_mongodb.odm.event_listener, event: 'prePersist' }
            - { name: doctrine_mongodb.odm.event_listener, event: 'preUpdate' }
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
```

### Extensions Compatible with ORM Only

> [!NOTE]
> This example shows the configuration when using the ORM and `DoctrineBundle` with a single default entity manager. When using an application with multiple managers, a separate tag is needed with the `connection` attribute for each connection.

```yaml
services:
    # Gedmo Uploadable Extension Listener
    gedmo.listener.uploadable:
        class: Gedmo\Uploadable\UploadableListener
        tags:
            - { name: doctrine.event_listener, event: 'loadClassMetadata'}
            - { name: doctrine.event_listener, event: 'preFlush'}
            - { name: doctrine.event_listener, event: 'onFlush'}
            - { name: doctrine.event_listener, event: 'postFlush'}
        calls:
            # Uncomment the below call if using attributes, and comment the call for the annotation reader
            # - [ setAnnotationReader, [ '@gedmo.mapping.driver.attribute' ] ]
            # The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0
            - [ setAnnotationReader, [ '@annotation_reader' ] ]
```

## Registering Mapping Configuration

When using the [Loggable](../loggable.md), [Translatable](../translatable.md), or [Tree](../tree.md) extensions, you will
need to register the mappings for these extensions to your object managers.

> [!NOTE]
> These extensions only provide mappings through annotations or attributes, with support for annotations being deprecated. If using annotations, you will need to ensure the [`doctrine/annotations`](https://www.doctrine-project.org/projects/annotations.html) library is installed and configured.

### MongoDB ODM Mapping

> [!IMPORTANT]
> The tree extension does NOT have any objects to map when using the MongoDB ODM.

The below example shows a configuration adding all available mappings to the default document manager. 

```yaml
doctrine_mongodb:
    document_managers:
        default:
            mappings:
                loggable:
                    type: attribute # or annotation
                    alias: GedmoLoggable
                    prefix: Gedmo\Loggable\Document
                    dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Loggable/Document"
                    is_bundle: false
                translatable:
                    type: attribute # or annotation
                    alias: GedmoTranslatable
                    prefix: Gedmo\Translatable\Document
                    dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Translatable/Document"
                    is_bundle: false
```

To verify your configuration, you can use the `doctrine:mongodb:mapping:info` command to make sure the entities are registered.

```shell
$ bin/console doctrine:mongodb:mapping:info
 Found X documents mapped in document manager default:
 [OK]   Gedmo\Loggable\Document\LogEntry
 [OK]   Gedmo\Loggable\Document\MappedSuperclass\AbstractLogEntry
 [OK]   Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation
 [OK]   Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation
 [OK]   Gedmo\Translatable\Document\Translation
```

### ORM Mapping

The below example shows a configuration adding all available mappings to the default entity manager. 

```yaml
doctrine:
    orm:
        default:
            mappings:
                loggable:
                    type: attribute # or annotation
                    alias: GedmoLoggable
                    prefix: Gedmo\Loggable\Entity
                    dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Loggable/Entity"
                    is_bundle: false
                translatable:
                    type: attribute # or annotation
                    alias: GedmoTranslatable
                    prefix: Gedmo\Translatable\Entity
                    dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Translatable/Entity"
                    is_bundle: false
                tree:
                    type: attribute # or annotation
                    alias: GedmoTree
                    prefix: Gedmo\Tree\Entity
                    dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Tree/Entity"
                    is_bundle: false
```

To verify your configuration, you can use the `doctrine:mapping:info` command to make sure the entities are registered.

```shell
$ bin/console doctrine:mapping:info
 Found X mapped entities:
 [OK]   Gedmo\Loggable\Entity\LogEntry
 [OK]   Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry
 [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
 [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
 [OK]   Gedmo\Translatable\Entity\Translation
 [OK]   Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure
```

## Registering Filters

### Soft Deleteable Filter

When using the [Soft Deleteable](../softdeleteable.md) extension, a filter is available which allows configuring whether
soft-deleted objects are included in query results.

> [!NOTE]
> The default configuration in the Symfony bundles does not enable the filters. These examples show how to globally enable them.

#### MongoDB ODM Filter Configuration

The below example shows a configuration adding the filter to the default document manager.

```yaml
doctrine_mongodb:
    document_managers:
        default:
            filters:
                'soft-deleteable':
                    class: Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter
                    enabled: true
```

#### ORM Filter Configuration

The below example shows a configuration adding the filter to the default entity manager.

```yaml
doctrine:
    orm:
        default:
            filters:
                'soft-deleteable':
                    class: Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter
                    enabled: true
```

## Configuring Extensions via Event Subscribers

When using the [Blameable](../blameable.md), [IP Traceable](../ip_traceable.md), [Loggable](../loggable.md), or
[Translatable](../translatable.md) extensions, to work correctly, they require extra information that must be set
at runtime, typically during the `kernel.request` event. The below example is an event subscriber class which configures
all of these extensions.

```php
<?php
namespace App\EventListener;

use Gedmo\Blameable\BlameableListener;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class GedmoExtensionsEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BlameableListener $blameableListener,
        private IpTraceableListener $ipTraceableListener,
        private LoggableListener $loggableListener,
        private TranslatableListener $translatableListener,
        private ?AuthorizationCheckerInterface $authorizationChecker = null,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['configureBlameableListener'], // Must run after the user is authenticated
                ['configureIpTraceableListener', 512], // Runs early since this only requires the Request object
                ['configureLoggableListener'], // Must run after the user is authenticated
                ['configureTranslatableListener'], // Must run after the locale is configured
            ],
        ];
    }

    /**
     * Configures the blameable listener using the currently authenticated user
     */
    public function configureBlameableListener(RequestEvent $event): void
    {
        // Only applies to the main request
        if (!$event->isMainRequest()) {
            return;
        }

        // If the required security component services weren't provided, there's nothing we can do
        if (null === $this->authorizationChecker || null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        // Only set the user information if there is a token in storage and it represents an authenticated user
        if (null !== $token && $this->authorizationChecker->isGranted('IS_AUTHENTICATED')) {
            $this->blameableListener->setUserValue($token->getUser());
        }
    }

    /**
     * Configures the IP traceable listener using the current request
     */
    public function configureIpTraceableListener(RequestEvent $event): void
    {
        // Only applies to the main request
        if (!$event->isMainRequest()) {
            return;
        }

        $ip = $event->getRequest()->getClientIp();

        // Only set the IP address if available
        if (null !== $ip) {
            $this->ipTraceableListener->setIpValue($ip);
        }
    }

    /**
     * Configures the loggable listener using the currently authenticated user
     */
    public function configureLoggableListener(RequestEvent $event): void
    {
        // Only applies to the main request
        if (!$event->isMainRequest()) {
            return;
        }

        // If the required security component services weren't provided, there's nothing we can do
        if (null === $this->authorizationChecker || null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        // Only set the user information if there is a token in storage and it represents an authenticated user
        if (null !== $token && $this->authorizationChecker->isGranted('IS_AUTHENTICATED')) {
            $this->loggableListener->setUsername($token->getUser());
        }
    }

    /**
     * Configures the translatable listener using the request locale
     */
    public function configureTranslatableListener(RequestEvent $event): void
    {
        $this->translatableListener->setTranslatableLocale($event->getRequest()->getLocale());
    }
}
```
