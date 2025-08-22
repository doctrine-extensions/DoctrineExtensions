# Sortable behavior extension for Doctrine

**Sortable** behavior will maintain a position field for ordering entities.

Features:
- Automatic handling of position index
- Group entity ordering by one or more fields
- Can be nested with other behaviors
- Annotation, Attribute and Xml mapping support for extensions

Contents:
- [Setup and autoloading](#setup-and-autoloading)
- [Sortable mapping](#sortable-mapping)
  - [Annotations](#annotation-mapping-example)
  - [Attributes](#attribute-mapping-example)
  - [Xml](#xml-mapping-example)
- [Basic usage examples](#basic-usage-examples)
- [Custom comparison method](#custom-comparison)

## Setup and autoloading
Read the [documentation](./annotations.md#em-setup) or check the [example code](../example)
on how to setup and use the extensions in most optimized way.

## Sortable mapping
- [SortableGroup](../src/Mapping/Annotation/SortableGroup.php) - used to specify property for **grouping**
- [SortablePosition](../src/Mapping/Annotation/SortablePosition.php) - used to specify property to store **position** index

|             | SortableGroup                               | SortablePosition                               |
|-------------|---------------------------------------------|------------------------------------------------|
| Annotations | `@Gedmo\Mapping\Annotation\SortableGroup`   | `@Gedmo\Mapping\Annotation\SortablePosition`   |
| Attributes  | `#[Gedmo\Mapping\Annotation\SortableGroup]` | `#[Gedmo\Mapping\Annotation\SortablePosition]` |
| Xml         | `<gedmo:sortable-group />`                  | `<gedmo:sortable-position />`                  |

> Implementing **[Sortable interface](../src/Sortable/Sortable.php) is optional**, except in cases there you need to identify entity as being Sortable.
> The metadata is loaded only once then cache is activated.

> You **should register [SortableRepository](../src/Sortable/Entity/Repository/SortableRepository.php)** (or a subclass) as the repository in the Entity
annotation to benefit from its query methods.

### Annotation mapping example

```php
<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }
}
```

### Attribute mapping example

```php
<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

#[ORM\Table(name: 'items')]
#[ORM\Entity(repositoryClass: SortableRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'integer', length: 64)]
    private string $name;

    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: 'integer')]
    private int $position;

    #[Gedmo\SortableGroup]
    #[ORM\Column(name: 'category', type: 'string', length: 128)]
    private string $category;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
```

### Xml mapping example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
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

## Basic usage examples

### To save **Items** at the end of the sorting list simply do:

```php
<?php
// By default, items are appended to the sorting list
$item1 = new Item();
$item1->setName('item 1');
$item1->setCategory('category 1');
$this->em->persist($item1);

$item2 = new Item();
$item2->setName('item 2');
$item2->setCategory('category 1');
$this->em->persist($item2);

$this->em->flush();

echo $item1->getPosition();
// prints: 0
echo $item2->getPosition();
// prints: 1
```

### Save **Item** at a given position

```php
<?php
$item1 = new Item();
$item1->setName('item 1');
$item1->setCategory('category 1');
$this->em->persist($item1);

$item2 = new Item();
$item2->setName('item 2');
$item2->setCategory('category 1');
$this->em->persist($item2);

$item0 = new Item();
$item0->setName('item 0');
$item0->setCategory('category 1');
$item0->setPosition(0);
$this->em->persist($item0);

$this->em->flush();

$repo = $this->em->getRepository('Entity\\Item');
$items = $repo->getBySortableGroupsQuery(array('category' => 'category 1'))->getResult();
foreach ($items as $item) {
    echo "{$item->getPosition()}: {$item->getName()}\n";
}
// prints:
// 0: item 0
// 1: item 1
// 2: item 2
```

### Reordering the sorted list

```php
<?php
$item1 = new Item();
$item1->setName('item 1');
$item1->setCategory('category 1');
$this->em->persist($item1);

$item2 = new Item();
$item2->setName('item 2');
$item2->setCategory('category 1');
$this->em->persist($item2);

$this->em->flush();

// Update the position of item2
$item2->setPosition(0);
$this->em->persist($item2);

$this->em->flush();

$repo = $this->em->getRepository('Entity\\Item');
$items = $repo->getBySortableGroupsQuery(array('category' => 'category 1'))->getResult();
foreach ($items as $item) {
    echo "{$item->getPosition()}: {$item->getName()}\n";
}
// prints:
// 0: item 2
// 1: item 1

```

### Using a foreign_key / relation as SortableGroup

If you want to use a foreign key / relation as sortable group, you have to put @Gedmo\SortableGroup annotation on ManyToOne annotation:

```
/**
 * @Gedmo\SortableGroup
 * @ORM\ManyToOne(targetEntity="Item", inversedBy="children")
 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
 */
private $parent;
```

To move an item at the end of the list, you can set the position to `-1`:

```
$item2->setPosition(-1);
```

## Custom comparison

Sortable works by comparing objects in the same group to see how they should be positioned. From time to time you may want to customize the way these
objects are compared by simply implementing the Doctrine\Common\Comparable interface

```php
<?php
namespace Entity;

use Doctrine\Common\Comparable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="items")
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class Item implements Comparable
{
    public function compareTo($other)
    {
        // return 1 if this object is considered greater than the compare value

        // return -1 if this object is considered less than the compare value

        // return 0 if this object is considered equal to the compare value
    }
}
```
