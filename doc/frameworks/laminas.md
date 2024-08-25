# Integrate the Doctrine Extensions in Laminas

This guide will demonstrate how to integrate the Doctrine Extensions library into a Laminas application.

## Index

- [Getting Started](#getting-started)
- [Registering Extension Listeners](#registering-extension-listeners)
- [Registering Mapping Configuration](#registering-mapping-configuration)
- [Registering Filters](#registering-filters)
- [Configuring Extensions via Event Listeners](#configuring-extensions-via-event-listeners)

## Getting Started

> [!TIP]
> This guide is written using the Laminas MVC quick start as the foundation.

Assuming you have already [created your Laminas application](https://docs.laminas.dev/laminas-mvc/quick-start/),
the next step will be to ensure you've installed this library and the Doctrine libraries you will need.

For Doctrine MongoDB ODM users, this Composer command will install all required dependencies:

```shell
composer require doctrine/doctrine-module doctrine/doctrine-mongo-odm-module doctrine/mongodb-odm gedmo/doctrine-extensions
```

For Doctrine ORM users, this Composer command will install all required dependencies:

```shell
composer require doctrine/dbal doctrine/doctrine-module doctrine/doctrine-orm-module doctrine/orm gedmo/doctrine-extensions
```

## Registering Extension Listeners

At the heart of the Doctrine Extensions library are the listeners which enable each extension. The below example demonstrates
how to register and enable all listeners provided by this library.

### Extensions Compatible with all Managers

```php
<?php

use Gedmo\Blameable\BlameableListener;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Sortable\SortableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'invokables' => [
            'gedmo.mapping.driver.attribute' => AttributeReader::class,
        ],
        'factories' => [
            'gedmo.listener.blameable' => function (ContainerInterface $container, string $requestedName): BlameableListener {
                $listener = new BlameableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.ip_traceable' => function (ContainerInterface $container, string $requestedName): IpTraceableListener {
                $listener = new IpTraceableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.loggable' => function (ContainerInterface $container, string $requestedName): LoggableListener {
                $listener = new LoggableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.sluggable' => function (ContainerInterface $container, string $requestedName): SluggableListener {
                $listener = new SluggableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.soft_deleteable' => function (ContainerInterface $container, string $requestedName): SoftDeleteableListener {
                $listener = new SoftDeleteableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                // If your application uses a PSR-20 clock, you can provide it to this listener by uncommenting the below line
                // $listener->setClock($container->get(ClockInterface::class));

                return $listener;
            },
            'gedmo.listener.sortable' => function (ContainerInterface $container, string $requestedName): SortableListener {
                $listener = new SortableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.timestampable' => function (ContainerInterface $container, string $requestedName): TimestampableListener {
                $listener = new TimestampableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                // If your application uses a PSR-20 clock, you can provide it to this listener by uncommenting the below line
                // $listener->setClock($container->get(ClockInterface::class));

                return $listener;
            },
            'gedmo.listener.translatable' => function (ContainerInterface $container, string $requestedName): TranslatableListener {
                $listener = new TranslatableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                // If your application uses a PSR-20 clock, you can provide it to this listener by uncommenting the below line
                // $listener->setClock($container->get(ClockInterface::class));

                return $listener;
            },
        ],
    ],
    'doctrine' => [
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    'gedmo.listener.blameable',
                    'gedmo.listener.ip_traceable',
                    'gedmo.listener.loggable',
                    'gedmo.listener.sluggable',
                    'gedmo.listener.soft_deleteable',
                    'gedmo.listener.sortable',
                    'gedmo.listener.timestampable',
                    'gedmo.listener.translatable',
                    'gedmo.listener.tree',
                ],
            ],
        ],
    ],
];
```

### Extensions Compatible with MongoDB ODM Only

```php
<?php

use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Gedmo\References\ReferencesListener;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'factories' => [
            'gedmo.listener.reference_integrity' => function (ContainerInterface $container, string $requestedName): ReferenceIntegrityListener {
                $listener = new ReferenceIntegrityListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
            'gedmo.listener.references' => function (ContainerInterface $container, string $requestedName): ReferencesListener {
                $listener = new ReferencesListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
        ],
    ],
    'doctrine' => [
        'eventmanager' => [
            'odm_default' => [
                'subscribers' => [
                    'gedmo.listener.reference_integrity',
                    'gedmo.listener.references',
                ],
            ],
        ],
    ],
];
```

### Extensions Compatible with ORM Only

```php
<?php

use Gedmo\Uploadable\UploadableListener;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'factories' => [
            'gedmo.listener.uploadable' => function (ContainerInterface $container, string $requestedName): UploadableListener {
                $listener = new UploadableListener();

                // This call configures the listener to use the attribute driver service created above; if using annotations, you will need to provide the appropriate service instead
                $listener->setAnnotationReader($container->get('gedmo.mapping.driver.attribute'));

                return $listener;
            },
        ],
    ],
    'doctrine' => [
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    'gedmo.listener.uploadable',
                ],
            ],
        ],
    ],
];
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

```php
<?php

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

return [
    'doctrine' => [
        'driver' => [
            'gedmo.odm_driver' => [
                'class' => AttributeDriver::class, // If your application is using annotations, use the AnnotationDriver class instead
                'paths' => [
                    '/path/to/vendor/gedmo/doctrine-extensions/src/Loggable/Document',
                    '/path/to/vendor/gedmo/doctrine-extensions/src/Translatable/Document',
                ],
            ],
            'odm_default' => [
                'drivers' => [
                    'gedmo.odm_driver', // Adds the mapping driver created above to the default mapping chain
                ],
            ],
        ],
    ],
];
```

### ORM Mapping

The below example shows a configuration adding all available mappings to the default entity manager.

```php
<?php

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

return [
    'doctrine' => [
        'driver' => [
            'gedmo.orm_driver' => [
                'class' => AttributeDriver::class, // If your application is using annotations, use the AnnotationDriver class instead
                'paths' => [
                    '/path/to/vendor/gedmo/doctrine-extensions/src/Loggable/Entity',
                    '/path/to/vendor/gedmo/doctrine-extensions/src/Translatable/Entity',
                    '/path/to/vendor/gedmo/doctrine-extensions/src/Tree/Entity',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    'gedmo.orm_driver', // Adds the mapping driver created above to the default mapping chain
                ],
            ],
        ],
    ],
];
```

To verify your configuration, you can use the `orm:info` command from the `doctrine-module` CLI tool to make sure the entities are registered.

```sh
$ vendor/bin/doctrine-module orm:info
 Found X mapped entities:

 [OK]   Gedmo\Loggable\Entity\LogEntry
 [OK]   Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry
 [OK]   Gedmo\Translatable\Entity\Translation
 [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
 [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
 [OK]   Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure
```

## Registering Filters

### Soft Deleteable Filter

When using the [Soft Deleteable](../softdeleteable.md) extension, a filter is available which allows configuring whether
soft-deleted objects are included in query results.

> [!NOTE]
> The default configuration in the Laminas modules does not enable the filters. To use these filters, you will need to enable them separately.

#### MongoDB ODM Filter Configuration

The below example shows a configuration adding the filter to the default document manager. To enable the filter,
you can follow the [Filters documentation guide](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/filters.html#disabling-enabling-filters-and-setting-parameters).

```php
<?php

use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;

return [
    'doctrine' => [
        'configuration' => [
            'odm_default' => [
                'filters' => [
                    'soft-deleteable' => SoftDeleteableFilter::class,
                ],
            ],
        ],
    ],
];
```

#### ORM Filter Configuration

The below example shows a configuration adding the filter to the default entity manager. To enable the filter,
you can follow the [Filters documentation guide](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/filters.html#disabling-enabling-filters-and-setting-parameters).

```php
<?php

use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

return [
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'filters' => [
                    'soft-deleteable' => SoftDeleteableFilter::class,
                ],
            ],
        ],
    ],
];
```

## Configuring Extensions via Event Listeners

When using the [Blameable](../blameable.md), [IP Traceable](../ip_traceable.md), [Loggable](../loggable.md), or
[Translatable](../translatable.md) extensions, to work correctly, they require extra information that must be set
at runtime.

**Help Improve This Documentation**

Pull requests are welcome to expand this section of the documentation.
