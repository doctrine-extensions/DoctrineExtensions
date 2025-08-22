# IP Traceable Behavior Extension for Doctrine

The **IP Traceable** behavior automates the update of IP addresses on your Doctrine objects.

## Index

- [Getting Started](#getting-started)
- [Configuring IP Traceable Objects](#configuring-ip-traceable-objects)
- [Using Traits](#using-traits)
- [Logging Changes For Specific Actions](#logging-changes-for-specific-actions)

## Getting Started

The IP traceable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\IpTraceable\IpTraceableListener;

$listener = new IpTraceableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

Then, once your application has it available, you can set the IP address to be recorded. The IP address can be set through
either an [IP address provider service](./utils/ip-address-provider.md) or by calling the listener's `setIpValue` method.

```php
// The $provider must be an implementation of Gedmo\Tool\IpAddressProviderInterface
$listener->setIpAddressProvider($provider);

$listener->setIpValue('127.0.0.1');
```

## Configuring IP Traceable Objects

The IP traceable extension can be configured with [annotations](./annotations.md#ip-traceable-extension),
[attributes](./attributes.md#ip-traceable-extension), or XML configuration (matching the mapping of
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

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable]
    public ?string $updatedFromIp = null;
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

        <field name="updatedFromIp" type="string">
            <gedmo:ip-traceable on="update"/>
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
     * @ORM\Column(type="string")
     * @Gedmo\IpTraceable
     */
    public ?string $updatedFromIp = null;
}
```

### Supported Field Types

The IP traceable extension supports the following field types for the IP address field:

- String (`string`, or when using the ORM and DBAL, `ascii_string`)

## Using Traits

The IP traceable extension provides traits which can be used to quickly add fields, and optionally the mapping configuration,
for a created by and updated by IP address to be updated for the **create** and **update** actions. These traits are
provided as a convenience for a common configuration, for other use cases it is suggested you add your own fields and configurations.

- `Gedmo\IpTraceable\Traits\IpTraceable` adds a `$createdFromIp` and `$updatedFromIp` property, with getters and setters
- `Gedmo\IpTraceable\Traits\IpTraceableDocument` adds a `$createdFromIp` and `$updatedFromIp` property, with getters and setters
  and mapping annotations and attributes for the MongoDB ODM
- `Gedmo\IpTraceable\Traits\IpTraceableEntity` adds a `$createdFromIp` and `$updatedFromIp` property, with getters and setters
  and mapping annotations and attributes for the ORM

## Logging Changes For Specific Actions

In addition to supporting logging the user for general create and update actions, the extension can also be configured to
log the IP address who made a change for specific fields or values.

### Single Field Changed To Specific Value

For example, we want to record the IP address who published an article on a news site. To do this, we add a field to our object
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
     * Field to track the IP address who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable]
    public ?string $updatedFromIp = null;

    /**
     * Field to track the IP address who published this article. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'published', value: true)]
    public ?string $publishedFromIp = null;
}
```

The change action can also be configured to watch for changes on related objects using a dot notation path. In this example,
we log the IP address who updated the article and placed it into an archived category.

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
     * Field to track the IP address who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable]
    public ?string $updatedFromIp = null;

    /**
     * Field to track the IP address who archived this article. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'category.archived', value: true)]
    public ?string $archivedFromIp = null;
}
```

### One of Many Fields Changed

The extension can also update a traceable field when using the **change** action by specifying a list of fields to watch.
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
     * Field to track the IP address who last made any change to this article. 
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable]
    public ?string $updatedFromIp = null;

    /**
     * Field to track the IP address who last modified this article's SEO metadata. 
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: ['metaDescription', 'metaKeywords', 'category.metaDescription', 'category.metaKeywords'])]
    public ?string $seoMetadataChangedFromIp = null;
}
```
