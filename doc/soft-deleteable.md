# Soft Deleteable Behavior Extension for Doctrine

The **Soft Deleteable** behavior allows you to "soft delete" objects by marking them as deleted with a timestamp instead
of removing them from the database.

## Index

- [Getting Started](#getting-started)
- [Configuring Soft Deleteable Objects](#configuring-soft-deleteable-objects)
- [Using Traits](#using-traits)
- [Working with Filters](#working-with-filters)
- [Bulk Delete Support](#bulk-delete-support)
- [Time-Aware Soft Deletion](#time-aware-soft-deletion)
- ["Hard Delete" Soft Deleted Records](#hard-delete-soft-deleted-records)

## Getting Started

The soft deleteable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\SoftDeleteable\SoftDeleteableListener;

$listener = new SoftDeleteableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

### Configuring Filters

To automatically filter out soft-deleted records from all queries, you need to register and enable the appropriate filter for your object manager.

#### For Doctrine ORM

```php
use Doctrine\ORM\Configuration;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

// Register the filter during configuration
$config = new Configuration();
$config->addFilter('soft-deleteable', SoftDeleteableFilter::class);

// Enable the filter (usually in your application bootstrap)
$em->getFilters()->enable('soft-deleteable');
```

#### For MongoDB ODM

```php
use Doctrine\ODM\MongoDB\Configuration;
use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;

// Register the filter during configuration
$config = new Configuration();
$config->addFilter('soft-deleteable', SoftDeleteableFilter::class);

// Enable the filter (usually in your application bootstrap)
$dm->getFilterCollection()->enable('soft-deleteable');
```

## Configuring Soft Deleteable Objects

The soft deleteable extension can be configured with [annotations](./annotations.md#soft-deleteable-extension),
[attributes](./attributes.md#soft-deleteable-extension), or XML configuration (matching the mapping of
your domain models). The full configuration for annotations and attributes can be reviewed in
the linked documentation.

The below examples show the basic configuration for the extension, marking a class as soft deleteable.

### Attribute Configuration

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\SoftDeleteable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;
}
```

### XML Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="App\Model\Article" table="articles">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string"/>
        <field name="deletedAt" type="datetime_immutable" nullable="true"/>

        <gedmo:soft-deleteable field-name="deletedAt"/>
    </entity>
</doctrine-mapping>
```

### Annotation Configuration

> [!NOTE]
> Support for annotations is deprecated and will be removed in 4.0.

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    public ?\DateTimeImmutable $deletedAt = null;
}
```

### Supported Field Types

The soft deleteable extension supports the following field types for the deletion timestamp field:

- Date (`date` and `date_immutable`)
- Time (`time` and `time_immutable`)
- Date/Time (`datetime` and `datetime_immutable`)
- Date/Time with timezone (`datetimetz` and `datetimetz_immutable`)
- Timestamp (`timestamp`)

## Using Traits

The soft deleteable extension provides traits which can be used to quickly add a deletion timestamp field, and
optionally the mapping configuration, to your models. This trait is provided as a convenience for common configurations.

- `Gedmo\SoftDeleteable\Traits\SoftDeleteable` adds a `$deletedAt` property with getter and setter
- `Gedmo\SoftDeleteable\Traits\SoftDeleteableDocument` adds a `$deletedAt` property with getters and setters
  and mapping annotations and attributes for the MongoDB ODM
- `Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity` adds a `$deletedAt` property with getters and setters
  and mapping annotations and attributes for the ORM

## Working with Filters

Once you have configured your soft deleteable objects and registered the appropriate filter, you can control the
visibility of soft-deleted records using the filter system.

### Basic Filter Operations

```php
// Enable the filter to hide soft-deleted records (recommended for most use cases)
$em->getFilters()->enable('soft-deleteable');

// Disable the filter to show all records, including soft-deleted ones
$em->getFilters()->disable('soft-deleteable');

// Check if the filter is enabled
$isEnabled = $em->getFilters()->isEnabled('soft-deleteable');
```

### Per-Object Filter Control

You can enable or disable the filter for specific object types using the enable and disable methods on the filter classes.
For example, when using the ORM:

```php
// Get the filter instance
$filter = $em->getFilters()->enable('soft-deleteable');

// Disable filtering for a specific entity (show all records, including soft-deleted)
$filter->disableForEntity(Article::class);

// Re-enable filtering for a specific entity
$filter->enableForEntity(Article::class);
```

For MongoDB ODM users, replace "Entity" with "Document" in the method names (i.e. `enableForDocument` and `disableForDocument`).

## Bulk DELETE Support

> [!NOTE]
> This feature is only available with the ORM.

The soft deleteable extension includes a query walker that automatically converts DQL DELETE statements into UPDATE
statements that set the deletion timestamp, allowing you to perform bulk soft-deletion operations.

### Using the Query Walker

To use DQL DELETE queries with soft deleteable entities, you need to specify the `SoftDeleteableWalker` as a custom output walker:

```php
use Doctrine\ORM\Query;
use Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker;

// Create a DQL DELETE query
$query = $em->createQuery('DELETE FROM App\Entity\Article a WHERE a.category = :category');
$query->setParameter('category', $category);

// Set the query walker to convert the DELETE query to UPDATE
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SoftDeleteableWalker::class);

// Execute the query
$query->execute();
```

## Time-Aware Soft Deletion

The soft deleteable extension supports "time-aware" deletion, where you can schedule objects for deletion at a future time.

### Enabling Time-Aware Support

```php
#[ORM\Entity]
#[Gedmo\SoftDeleteable(timeAware: true)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;
}
```

### Usage Example

```php
// Schedule an article for deletion in the future
$article = new Article();
$article->setTitle('Scheduled for deletion');
$article->setDeletedAt(new \DateTimeImmutable('+1 week')); // Delete in 1 week
$em->persist($article);
$em->flush();

// The article will be visible now (deletion time hasn't passed)
$found = $em->getRepository(Article::class)->findOneBy(['title' => 'Scheduled for deletion']);
assert($found !== null); // Found because deletion time is in the future

// After the scheduled time passes, the article will be automatically filtered out
// (without needing to run any cleanup processes)
```

## "Hard Delete" Soft Deleted Records

By default, the soft deleteable extension allows soft deleted records to be "hard deleted" (fully removed from the database)
by deleting them a second time. However, by setting the `hardDelete` parameter in the configuration to `false`, you can
prevent soft deleted records from being deleted at all.

## Setting the non-deleted value  

By default a record set to null will be seen as not (yet) soft-deleted.  
This can be overwritten by setting `nonDeletedColumnValue` on the specified entity

```php
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', hardDelete: false, nonDeletedColumnValue: '1970-01-01 00:00:00')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;
}
```