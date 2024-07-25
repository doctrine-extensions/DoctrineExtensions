# Blameable Behavior Extension for Doctrine

The **Blameable** behavior automates the update of user information on your Doctrine objects.

## Index

- [Getting Started](#getting-started)
- [Configuring Blameable Objects](#configuring-blameable-objects)
- [Using Traits](#using-traits)
- [Logging Changes For Specific Actions](#logging-changes-for-specific-actions)

## Getting Started

The blameable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\Blameable\BlameableListener;

$listener = new BlameableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

Then, once your application has it available (i.e. after validating the authentication for your user during an HTTP request),
you can set a reference to the user to be blamed for changes by calling the listener's `setUserValue` method.

```php
// The $user can be either an object or a string
$listener->setUserValue($user);
```

## Configuring Blameable Objects

The blameable extension can be configured with [annotations](./annotations.md#blameable-extension),
[attributes](./attributes.md#blameable-extension), or XML configuration (matching the mapping of
your domain models). The full configuration for annotations and attributes can be reviewed in
the linked documentation.

The below examples show the simplest and default configuration for the extension, setting a field
when the model is updated.

### Annotation Configuration

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
     * @ORM\Column(type="string")
     * @Gedmo\Blameable
     */
    public ?string $updatedBy = null;
}
```

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

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Blameable]
    public ?string $updatedBy = null;
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

        <field name="updatedBy" type="string">
            <gedmo:blameable on="update"/>
        </field>
    </entity>
</doctrine-mapping>
```

### Supported Field Types

The blameable extension supports the following field types for a blameable field:

- String (`string`, or when using the ORM and DBAL, `ascii_string`)
- Integer (`int` only)
- UUID or ULID (requires a third party package providing a `uuid` or `ulid` Doctrine type; popular packages for
  the ORM and DBAL include [`ramsey/uuid-doctrine`](https://github.com/ramsey/uuid-doctrine) and
  [`symfony/doctrine-bridge`](https://github.com/symfony/doctrine-bridge))
- A many-to-one association (ORM) or reference many reference (MongoDB ODM)

## Using Traits

The blameable extension provides traits which can be used to quickly add fields, and optionally the mapping configuration,
for a created by and updated by username to be updated for the **create** and **update** blameable actions. These traits are
provided as a convenience for a common configuration, for other use cases it is suggested you add your own fields and configurations.

- `Gedmo\Blameable\Traits\Blameable` adds a `$createdBy` and `$updatedBy` property, with getters and setters
- `Gedmo\Blameable\Traits\BlameableDocument` adds a `$createdBy` and `$updatedBy` property, with getters and setters
  and mapping annotations and attributes for the MongoDB ODM
- `Gedmo\Blameable\Traits\BlameableEntity` adds a `$createdBy` and `$updatedBy` property, with getters and setters
  and mapping annotations and attributes for the ORM

## Logging Changes For Specific Actions

In addition to supporting logging the user for general create and update actions, the extension can also be configured to
log the user who made a change for specific fields or values.

### Single Field Changed To Specific Value

For example, we want to record the user who published an article on a news site. To do this, we add a field to our object
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
     * Field to track the user who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Blameable]
    public ?string $updatedBy = null;

    /**
     * Field to track the user who published this article. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'published', value: true)]
    public ?string $publishedBy = null;
}
```

The change action can also be configured to watch for changes on related objects using a dot notation path. In this example,
we log the user who updated the article and placed it into an archived category.

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
     * Field to track the user who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Blameable]
    public ?string $updatedBy = null;

    /**
     * Field to track the user who archived this article. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'category.archived', value: true)]
    public ?string $archivedBy = null;
}
```

### One of Many Fields Changed

The extension can also update a blameable field when using the **change** action by specifying a list of fields to watch.
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
     * Field to track the user who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Blameable]
    public ?string $updatedBy = null;

    /**
     * Field to track the user who last modified this article's SEO metadata. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: ['metaDescription', 'metaKeywords', 'category.metaDescription', 'category.metaKeywords'])]
    public ?string $seoMetadataChangedBy = null;
}
```
