# SoftDeleteable behavior extension for Doctrine 2

**SoftDeleteable** behavior allows to "soft delete" objects, filtering them
at SELECT time by marking them as with a timestamp, but not explicitly removing them from the database.

Features:

- Works with DQL DELETE queries (using a Query Hint).
- All SELECT queries will be filtered, not matter from where they are executed (Repositories, DQL SELECT queries, etc).
- For now, it works only with the ORM
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions
- Support for 'timeAware' option: When creating an entity set a date of deletion in the future and never worry about cleaning up at expiration time.

[blog_reference]: http://gediminasm.org/article/tree-nestedset-behavior-extension-for-doctrine-2 "Softdeleteable - does not explicitly remove record entries from the database, instead it marks those as deleted and skips in selection queries"
[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Usage [examples](#usage)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

With SoftDeleteable there's one more step you need to do. You need to add the filter to your configuration:

``` php

$config = new Doctrine\ORM\Configuration;

// Your configs..

$config->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
```

And then you can access the filter from your EntityManager to enable or disable it with the following code:

``` php
// This will enable the SoftDeleteable filter, so entities which were "soft-deleted" will not appear
// in results
// You should adapt the filter name to your configuration (ex: softdeleteable)
$em->getFilters()->enable('soft-deleteable');

// This will disable the SoftDeleteable filter, so entities which were "soft-deleted" will appear in results
$em->getFilters()->disable('soft-deleteable');
```

<a name="entity-mapping"></a>

## SoftDeleteable Entity example:

### SoftDeleteable annotations:
- **@Gedmo\Mapping\Annotation\SoftDeleteable** this class annotation tells if a class is SoftDeleteable. It has a
mandatory parameter "fieldName", which is the name of the field to be used to hold the known "deletedAt" field. It
must be of any of the date types.

Available configuration options:
- **fieldName** - The name of the field that will be used to determine if the object is removed or not (NULL means
it's not removed. A date value means it was removed). NOTE: The field MUST be nullable.

**Note:** that SoftDeleteable interface is not necessary, except in cases there
you need to identify entity as being SoftDeleteable. The metadata is loaded only once then
cache is activated

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}
```

<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
---
Entity\Article:
  type: entity
  table: articles
  gedmo:
    soft_deleteable:
      field_name: deletedAt
      time_aware: false
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    title:
      type: string
    deletedAt:
      type: date
      nullable: true
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\Timestampable" table="timestampables">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string" />

        <field name="deletedAt" type="datetime" nullable="true" />

        <gedmo:soft-deleteable field-name="deletedAt" time-aware="false" />
    </entity>

</doctrine-mapping>
```

<a name="usage"></a>

## Usage:

``` php
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
```

Easy like that, any suggestions on improvements are very welcome.
