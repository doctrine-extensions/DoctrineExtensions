# UPGRADE FROM 2.x to 3.0

The upgrade should not cause data inconsistencies and it can be applied on
running production databases.

### Sortable

Only metadata mapping has changed and from now on, it allows more than one
sortable field per entity.

#### Annotation mapping

**Before:**

``` php
/**
 * @ORM\Table(name="items")
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class Item
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    private $name;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(name="category", type="string", length=128)
     */
    private $category;
}
```

**After:**

``` php
/**
 * @ORM\Table(name="items")
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class Item
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $name;

    /**
     * @Gedmo\Sortable(groups={"category"})
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\Column(length=128)
     */
    private $category;
}
```

#### Yaml

**Before:**

``` yaml
---
Entity\Item:
  type: entity
  table: items
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    name:
      type: string
      length: 64
    position:
      type: integer
      gedmo:
        - sortablePosition
    category:
      type: string
      length: 128
      gedmo:
        - sortableGroup
```

**After:**

``` yaml
---
Entity\Item:
  type: entity
  table: items
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    name:
      type: string
      length: 64
    position:
      type: integer
      gedmo:
        sortable:
          groups: [category]
    category:
      type: string
      length: 128
```

#### XML

**Before:**

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions-2.4.xsd">
    <entity name="Entity\Item" table="items">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="name" type="string" length="128">
        </field>

        <field name="position" type="integer">
            <gedmo:sortable-position/>
        </field>
        <field name="category" type="string" length="128">
            <gedmo:sortable-group />
        </field>
    </entity>
</doctrine-mapping>
```

**After:**

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions-3.0.xsd">
    <entity name="Entity\Item" table="items">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="name" type="string" length="128">
        </field>

        <field name="position" type="integer">
            <gedmo:sortable groups="category"/>
        </field>
        <field name="category" type="string" length="128"/>
    </entity>
</doctrine-mapping>
```

## Uploadable

Uploadable now supports more than one file per entity. Should not impact
users unless they have used custom **FilenameGeneratorInterface**

- A new parameter `identifier` is also added to
  `FilenameGeneratorInterface::generate()` as the forth argument.
  Currently this is used by the `FilenameGeneratorSha1` class to prevent
  having the same SHA1 result when the same filename and extension are
  passed to different file fields (with different identifiers) in the same
  entity. This may affect users who implements their own
  `FilenameGeneratorInterface`.
- Visibility of method `processFile()` and `moveFile()` in
  `UploadableListener`changed from `public` to `protected` because their
  signature are changed and they don't look right to be public. This may
  affect users who override the original `UploadableListener`.
