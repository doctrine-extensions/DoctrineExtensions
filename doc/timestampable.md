# Timestampable Behavior Extension for Doctrine

The **Timestampable** behavior automates the update of timestamps on your Doctrine objects.

## Index

- [Getting Started](#getting-started)
- [Configuring Timestampable Objects](#configuring-timestampable-objects)
- [Using Traits](#using-traits)
- [Logging Changes For Specific Actions](#logging-changes-for-specific-actions)

## Getting Started

The timestampable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\Timestampable\TimestampableListener;

$listener = new TimestampableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

### Using a Clock

The timestampable extension supports using a [PSR-20 Clock](https://www.php-fig.org/psr/psr-20/) as the provider for its
timestamps, falling back to creating a new `DateTime` instance when not available.

To use a clock in the timestampable extension, you can provide one by calling the listener's `setClock` method.

```php
$listener->setClock($clock);
```

## Configuring Timestampable Objects

The Itimestampable extension can be configured with [annotations](./annotations.md#timestampable-extension),
[attributes](./attributes.md#timestampable-extension), or XML configuration (matching the mapping of
your domain models). The full configuration for annotations and attributes can be reviewed in
the linked documentation.

The below examples show the simplest and default configuration for the extension, setting a field
when the model is updated.

### Attribute Configuration

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $updatedAt = null;
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

        <field name="updatedAt" type="string">
            <gedmo:timestampable on="update"/>
        </field>
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
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable
     */
    public ?\DateTimeImmutable $updatedAt = null;
}
```

### Supported Field Types

The timestampable extension supports the following field types for the timestamp field:

- Date (`date` and `date_immutable`)
- Time (`time` and `time_immutable`)
- Date/Time (`datetime` and `datetime_immutable`)
- Date/Time with timezone (`datetimetz` and `datetimetz_immutable`)
- Timestamp (`timestamp`)
- Variable Date/Time (`vardatetime`) (Supported by the ORM and DBAL only)
- Integer (`integer` only)

## Using Traits

The timestampable extension provides traits which can be used to quickly add fields, and optionally the mapping configuration,
for a created at and updated at timestamp to be updated for the **create** and **update** actions. These traits are
provided as a convenience for a common configuration, for other use cases it is suggested you add your own fields and configurations.

- `Gedmo\Timestampable\Traits\Timestampable` adds a `$createdAt` and `$updatedAt` property, with getters and setters
- `Gedmo\Timestampable\Traits\TimestampableDocument` adds a `$createdAt` and `$updatedAt` property, with getters and setters
  and mapping annotations and attributes for the MongoDB ODM
- `Gedmo\Timestampable\Traits\TimestampableEntity` adds a `$createdAt` and `$updatedAt` property, with getters and setters
  and mapping annotations and attributes for the ORM

## Logging Changes For Specific Actions

In addition to supporting logging the timestamp for general create and update actions, the extension can also be configured to
log the timestamp for a change for specific fields or values.

### Single Field Changed To Specific Value

For example, we want to record the timestamp of when an article is published on a news site. To do this, we add a field to our object
and configure it using the **change** action, specifying the field and value we want it to match.

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $published = false;

    /**
     * Field to track the timestamp for the last change made to this article. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $updatedAt = null;

    /**
     * Field to track the timestamp for when this article was published. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'published', value: true)]
    public ?\DateTimeImmutable $updatedAt = null;
}
```

The change action can also be configured to watch for changes on related objects using a dot notation path. In this example,
we log the timestamp for when the article was moved into an archived category.

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    public ?Category $category = null;

    /**
     * Field to track the timestamp for the last change made to this article. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $updatedAt = null;

    /**
     * Field to track the timestamp for when this article was archived. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'category.archived', value: true)]
    public ?\DateTimeImmutable $updatedAt = null;
}
```

### One of Many Fields Changed

The extension can also update a timestampable field when using the **change** action by specifying a list of fields to watch.
This also supports the dotted path notation, allowing you to watch changes on the model itself as well as related data.

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    public ?Category $category = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $metaDescription = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $metaKeywords = null;

    /**
     * Field to track the timestamp for the last change made to this article. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $updatedAt = null;

    /**
     * Field to track the timestamp for when this article's SEO metadata was last updated. 
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: ['metaDescription', 'metaKeywords', 'category.metaDescription', 'category.metaKeywords'])]
    public ?\DateTimeImmutable $seoMetadataChangedAt = null;
}
```
