# SoftDeleteable behavior extension for Doctrine

**SoftDeleteable** behavior allows to "soft delete" objects, filtering them
at SELECT time by marking them deleted as with a timestamp, but not explicitly removing them from the database.

Features:

- Works with DQL DELETE queries (using a Query Hint).
- All SELECT queries will be filtered, not matter from where they are executed (Repositories, DQL SELECT queries, etc).
- For now, it works only with the ORM
- Can be nested with other behaviors
- Attribute, Annotation and Xml mapping support for extensions
- Support for 'timeAware' option: When creating an entity set a date of deletion in the future and never worry about cleaning up at expiration time.
- Support for 'hardDelete' option: When deleting a second time it allows to disable hard delete.

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- [Xml](#xml-mapping) mapping example
- Usage [examples](#usage)
- Using [Traits](#traits)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in most optimized way.

With SoftDeleteable there's one more step you need to do. You need to add the filter to your configuration:

```php

$config = new Doctrine\ORM\Configuration;

// Your configs..

$config->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
```

And then you can access the filter from your EntityManager to enable or disable it with the following code:

```php
// This will enable the SoftDeleteable filter, so entities which were "soft-deleted" will not appear
// in results
// You should adapt the filter name to your configuration (ex: softdeleteable)
$em->getFilters()->enable('soft-deleteable');

// This will disable the SoftDeleteable filter, so entities which were "soft-deleted" will appear in results
$em->getFilters()->disable('soft-deleteable');
```

Or from your DocumentManager (ODM):

```php
// This will enable the SoftDeleteable filter, so entities which were "soft-deleted" will not appear
// in results
// You should adapt the filter name to your configuration (ex: softdeleteable)
$em->getFilterCollection()->enable('soft-deleteable');

// This will disable the SoftDeleteable filter, so entities which were "soft-deleted" will appear in results
$em->getFilterCollection()->disable('soft-deleteable');
```

**NOTE:** by default all filters are disabled, so you must explicitly enable **soft-deleteable** filter in your setup
or whenever you need it.

<a name="entity-mapping"></a>

## SoftDeleteable Entity example:

### SoftDeleteable annotations:
- **@Gedmo\Mapping\Annotation\SoftDeleteable** this class annotation tells if a class is SoftDeleteable. It has a
mandatory parameter "fieldName", which is the name of the field to be used to hold the known "deletedAt" field. It
must be of any of the date types.

### SoftDeleteable attributes:
- **\#[Gedmo\Mapping\Annotation\SoftDeleteable]** this class attribute tells if a class is SoftDeleteable. It has a
mandatory parameter "fieldName", which is the name of the field to be used to hold the known "deletedAt" field. It
must be of any of the date types.

Available configuration options:
- **fieldName** - The name of the field that will be used to determine if the object is removed or not (NULL means
it's not removed. A date value means it was removed). NOTE: The field MUST be nullable.

- **hardDelete** - A boolean to enable or disable hard delete after soft delete has already been done. NOTE: Set to true by default.

**Note:** that SoftDeleteable interface is not necessary, except in cases where
you need to identify entity as being SoftDeleteable. The metadata is loaded only once then
cache is activated.

**Note:** this example is using annotations and attributes for mapping, you should use
one of them, not both.

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Article
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTIFY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string")
     */
    #[ORM\Column(name: 'title', type: Types::STRING)]
    private $title;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    #[ORM\Column(name: 'deletedAt', type: Types::DATETIME_MUTABLE, nullable: true)]
    private $deletedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }
}
```

<a name="xml-mapping"></a>

## Xml mapping example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\Timestampable" table="timestampables">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string" />

        <field name="deletedAt" type="datetime" nullable="true" />

        <gedmo:soft-deleteable field-name="deletedAt" time-aware="false" hard-delete="true" />
    </entity>

</doctrine-mapping>
```

<a name="usage"></a>

## Usage:

```php
<?php
$article = new Article;
$article->setTitle('My Article');

$em->persist($article);
$em->flush();

// Now if we remove it, it will set the deletedAt field to the actual date
$em->remove($article);
$em->flush();

$repo = $em->getRepository('Article');
$art = $repo->findOneBy(array('title' => 'My Article'));

// It should NOT return the article now
$this->assertNull($art);

// But if we disable the filter, the article should appear now
$em->getFilters()->disable('soft-deleteable');

$art = $repo->findOneBy(array('title' => 'My Article'));

$this->assertTrue(is_object($art));

// Enable / Disable filter filter, for specified entity (default is enabled for all)
$filter = $em->getFilters()->enable('soft-deleteable');
$filter->disableForEntity('Entity\Article');
$filter->enableForEntity('Entity\Article');

// Undelete the entity by setting the deletedAt field to null
$article->setDeletedAt(null);
```

Easy like that, any suggestions on improvements are very welcome.

<a name="traits"></a>

## Traits

You can use softDeleteable traits for quick **deletedAt** timestamp definitions
when using annotation mapping.
There is also a trait without annotations for easy integration purposes.

**Note:** this feature is only available since php **5.4.0**. And you are not required
to use the Traits provided by extensions.

**Note:** this example is using annotations and attributes for mapping, you should use
one of them, not both.

```php
<?php
namespace SoftDeleteable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class UsingTrait
{
    /**
     * Hook SoftDeleteable behavior
     * updates deletedAt field
     */
    use SoftDeleteableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    private $title;
}
```

Traits are very simple and if you use different field names I recommend to simply create your
own ones based per project. These ones are standing as an example.
