# Tree - Nestedset behavior extension for Doctrine

**Tree** nested behavior will implement the standard Nested-Set behavior
on your Entity. Tree supports different strategies. Currently it supports
**nested-set**, **closure-table** and **materialized-path**. Also this behavior can be nested
with other extensions to translate or generated slugs of your tree nodes.

Features:

- Materialized Path strategy for ORM and ODM (MongoDB)
- Closure tree strategy, may be faster in some cases where ordering does not matter
- Support for multiple roots in nested-set
- No need for other managers, implementation is through event listener
- Synchronization of left, right values is automatic
- Can support concurrent flush with many objects being persisted and updated
- Can be nested with other extensions
- Attribute, Annotation and Xml mapping support for extensions

Thanks for contributions to:

- **[comfortablynumb](https://github.com/comfortablynumb) Gustavo Falco** for Closure and Materialized Path strategy
- **[everzet](https://github.com/everzet) Kudryashov Konstantin** for TreeLevel implementation
- **[stof](https://github.com/stof) Christophe Coevoet** for getTreeLeafs function

**Note:**

- After using a NestedTreeRepository functions: **verify, recover, removeFromTree** it is recommended to clear the EntityManager cache
because nodes may have changed values in database but not in memory. Flushing dirty nodes can lead to unexpected behaviour.
- Closure tree implementation is experimental and not fully functional, so far not documented either
- Public [Tree repository](https://github.com/doctrine-extensions/DoctrineExtensions "Tree extension on Github") is available on github

This article will cover the basic installation and functionality of **Tree** behavior

Content:

- [Including](#including-extension) the extension
- Tree [annotations](#annotations)
- Entity [example](#entity-mapping)
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)
- Build [html tree](#html-tree)
- Advanced usage [examples](#advanced-examples)
- [Materialized Path](#materialized-path)
- [Closure Table](#closure-table)
- [Repository methods (all strategies)](#repository-methods)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in the most optimized way.

<a name="entity-mapping"></a>

## Tree Entity example:

**Note:** Node interface is not necessary, except in cases where
you need to identify and entity as being a Tree Node. The metadata is loaded only once when the
cache is activated

**Note:** this example is using annotations and attributes for mapping, you should use
one of them, not both.

```php
<?php
namespace Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="categories")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'categories')]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
class Category
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private $title;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private $lvl;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    private $rgt;

    /**
     * @var self|null
     *
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $root;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $parent;

    /**
     * @var Collection<int, Category>
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private $children;

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

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setParent(self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
```

<a name="annotations"></a>

### Tree annotations and attributes:

These classes can be used either as annotation or as attribute:

- **@Gedmo\Mapping\Annotation\Tree(type="strategy")** this **class annotation/attribute** sets the tree strategy by using the **type** parameter.
Currently **nested**, **closure** or **materializedPath** strategies are supported. An additional "activateLocking" parameter
is available if you use the "Materialized Path" strategy with MongoDB. It's used to activate the locking mechanism (more on that
in the corresponding section).
- **@Gedmo\Mapping\Annotation\TreeLeft** field is used to store the tree **left** value
- **@Gedmo\Mapping\Annotation\TreeRight** field is used to store the tree **right** value
- **@Gedmo\Mapping\Annotation\TreeParent** will identify the column as the relation to **parent node**
- **@Gedmo\Mapping\Annotation\TreeLevel** field is used to store the tree **level**
- **@Gedmo\Mapping\Annotation\TreeRoot** field is used to store the tree **root** id value or identify the column as the relation to **root node**
- **@Gedmo\Mapping\Annotation\TreePath** (Materialized Path only) field is used to store the **path**. It has an
optional parameter "separator" to define the separator used in the path.
- **@Gedmo\Mapping\Annotation\TreePathSource** (Materialized Path only) field is used as the source to
 construct the "path"
- **@Gedmo\Mapping\Annotation\TreeLockTime** (Materialized Path - ODM MongoDB only) field is used if you need to
use the locking mechanism with MongoDB. It persists the lock time if a root node is locked (more on that in the corresponding
section).

<a name="xml-mapping"></a>

## Xml mapping example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\NestedTree" table="nested_trees" repository-class="Gedmo\Tree\Entity\Repository\NestedTreeRepository">

        <indexes>
            <index name="name_idx" columns="name"/>
        </indexes>

        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="name" type="string" length="128"/>
        <field name="left" column="lft" type="integer">
            <gedmo:tree-left/>
        </field>
        <field name="right" column="rgt" type="integer">
            <gedmo:tree-right/>
        </field>
        <field name="level" column="lvl" type="integer">
            <gedmo:tree-level/>
        </field>

        <many-to-one field="root" target-entity="NestedTree">
            <join-column name="tree_root" referenced-column-name="id" on-delete="CASCADE"/>
            <gedmo:tree-root/>
        </many-to-one>

        <many-to-one field="parent" target-entity="NestedTree" inversed-by="children">
            <join-column name="parent_id" referenced-column-name="id" on-delete="CASCADE"/>
            <gedmo:tree-parent/>
        </many-to-one>

        <one-to-many field="children" target-entity="NestedTree" mapped-by="parent">
            <order-by>
                <order-by-field name="left" direction="ASC" />
            </order-by>
        </one-to-many>

        <gedmo:tree type="nested"/>

    </entity>

</doctrine-mapping>
```

<a name="basic-examples"></a>

## Basic usage examples:

### To save some **Categories** and generate tree:

```php
<?php
$food = new Category();
$food->setTitle('Food');

$fruits = new Category();
$fruits->setTitle('Fruits');
$fruits->setParent($food);

$vegetables = new Category();
$vegetables->setTitle('Vegetables');
$vegetables->setParent($food);

$carrots = new Category();
$carrots->setTitle('Carrots');
$carrots->setParent($vegetables);

$this->em->persist($food);
$this->em->persist($fruits);
$this->em->persist($vegetables);
$this->em->persist($carrots);
$this->em->flush();
```

The result after flush will generate the food tree:

```
/food (1-8)
    /fruits (2-3)
    /vegetables (4-7)
        /carrots (5-6)
```

### Using repository functions

```php
<?php
$repo = $em->getRepository(Category::class);

$food = $repo->findOneByTitle('Food');
echo $repo->childCount($food);
// prints: 3
echo $repo->childCount($food, true/*direct*/);
// prints: 2
$children = $repo->children($food);
// $children contains:
// 3 nodes
$children = $repo->children($food, false, 'title');
// will sort the children by title
$carrots = $repo->findOneByTitle('Carrots');
$path = $repo->getPath($carrots);
/* $path contains:
   0 => Food
   1 => Vegetables
   2 => Carrots
*/

// verification and recovery of tree
$repo->verify();
// can return TRUE if tree is valid, or array of errors found on tree
$repo->recover();
$em->flush(); // important: flush recovered nodes
// if tree has errors it will try to fix all tree nodes

// UNSAFE: be sure to backup before running this method when necessary, if you can use $em->remove($node);
// which would cascade to children
// single node removal
$vegies = $repo->findOneByTitle('Vegetables');
$repo->removeFromTree($vegies);
$em->clear(); // clear cached nodes
// it will remove this node from tree and reparent all children

// reordering the tree
$food = $repo->findOneByTitle('Food');
$repo->reorder($food, 'title');
// it will reorder all "Food" tree node left-right values by the title
```

### Inserting node in different positions

```php
<?php
$food = new Category();
$food->setTitle('Food');

$fruits = new Category();
$fruits->setTitle('Fruits');

$vegetables = new Category();
$vegetables->setTitle('Vegetables');

$carrots = new Category();
$carrots->setTitle('Carrots');

$treeRepository
    ->persistAsFirstChild($food)
    ->persistAsFirstChildOf($fruits, $food)
    ->persistAsLastChildOf($vegetables, $food)
    ->persistAsNextSiblingOf($carrots, $fruits);

$em->flush();
```

For more details you can check the **NestedTreeRepository** __call function

Moving up and down the nodes in same level:

Tree example:

```
/Food
    /Vegetables
        /Onions
        /Carrots
        /Cabbages
        /Potatoes
    /Fruits
```

Now move **carrots** up by one position

```php
<?php
$repo = $em->getRepository(Category::class);
$carrots = $repo->findOneByTitle('Carrots');
// move it up by one position
$repo->moveUp($carrots, 1);
```

Tree after moving the Carrots up:

```
/Food
    /Vegetables
        /Carrots <- moved up
        /Onions
        /Cabbages
        /Potatoes
    /Fruits
```

Moving **carrots** down to the last position

```php
<?php
$repo = $em->getRepository(Category::class);
$carrots = $repo->findOneByTitle('Carrots');
// move it down to the end
$repo->moveDown($carrots, true);
```

Tree after moving the Carrots down as last child:

```
/Food
    /Vegetables
        /Onions
        /Cabbages
        /Potatoes
        /Carrots <- moved down to the end
    /Fruits
```

**Note:** the tree repository functions **verify, recover, removeFromTree**
will require you to clear the cache of the Entity Manager because left-right values will differ.
So after that use **$em->clear();** if you will continue using the nodes after these operations.

### If you need a repository for your TreeNode Entity simply extend it

```php
<?php
namespace Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

class CategoryRepository extends NestedTreeRepository
{
    // your code here
}

// and then on your entity link to this repository

/**
 * @Gedmo\Tree(type="nested")
 * @Entity(repositoryClass="Entity\Repository\CategoryRepository")
 */
#[Gedmo\Tree(type: 'nested')]
#[Entity(repositoryClass: \App\Entity\Repository\CategoryRepository::class)]
class Category
{
    //...
}
```

<a name="html-tree"></a>

## Create html tree:

### Retrieving the whole tree as an array

If you would like to load the whole tree as a node array hierarchy use:

```php
<?php
$repo = $em->getRepository(Category::class);
$arrayTree = $repo->childrenHierarchy();
```

All node children are stored under the **__children** key for each node.

### Retrieving as html tree

To load a tree as a **ul - li** html tree use:

```php
<?php
$repo = $em->getRepository(Category::class);
$htmlTree = $repo->childrenHierarchy(
    null, /* starting from root nodes */
    false, /* true: load all children, false: only direct */
    [
        'decorate' => true,
        'representationField' => 'slug',
        'html' => true
    ]
);
```

### Customize html tree output

```php
<?php
$repo = $em->getRepository(Category::class);
$options = [
    'decorate' => true,
    'rootOpen' => '<ul>',
    'rootClose' => '</ul>',
    'childOpen' => '<li>',
    'childClose' => '</li>',
    'nodeDecorator' => function($node) {
        return '<a href="/page/'.$node['slug'].'">'.$node[$field].'</a>';
    }
];
$htmlTree = $repo->childrenHierarchy(
    null, /* starting from root nodes */
    false, /* true: load all children, false: only direct */
    $options
);

```

### Generate your own node list

```php
<?php
$repo = $em->getRepository(Category::class);
$query = $entityManager
    ->createQueryBuilder()
    ->select('node')
    ->from(Category::class, 'node')
    ->orderBy('node.root, node.lft', 'ASC')
    ->where('node.root = 1')
    ->getQuery()
;
$options = ['decorate' => true];
$tree = $repo->buildTree($query->getArrayResult(), $options);
```

### Using routes in decorator, show only selected items, return unlimited levels items as 2 levels

```php
<?php
$controller = $this;
$tree = $root->childrenHierarchy(null, false, [
    'decorate' => true,
    'rootOpen' => static function (array $tree): ?string {
        if ([] !== $tree && 0 == $tree[0]['lvl']) {
            return '<div class="catalog-list">';
        }

        return null;
    },
    'rootClose' => static function (array $child): ?string {
        if ([] !== $child && 0 == $child[0]['lvl']) {
            return '</div>';
        }

        return null;
     },
    'childOpen' => '',
    'childClose' => '',
    'nodeDecorator' => static function (array $node) use (&$controller): ?string {
        if (1 == $node['lvl']) {
            return '<h1>'.$node['title'].'</h1>';
        }

        if ($node["isVisibleOnHome"]) {
            return '<a href="'.$controller->generateUrl("wareTree", ["id"=>$node['id']]).'">'.$node['title'].'</a>&nbsp;';
        }

        return null;
    }
]);
```

<a name="advanced-examples"></a>

## Building trees from your entities

You can use the `childrenHierarchy` method to build an array tree from your result set.
However, sometimes it is more convenient to work with the entities directly. The `TreeObjectHydrator`
lets you build a tree from your entities instead, without triggering any more queries.

First, you have to register the hydrator in your Doctrine entity manager.

```php
<?php
$em->getConfiguration()->addCustomHydrationMode('tree', 'Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator');
```

The hydrator requires the `HINT_INCLUDE_META_COLUMNS` query hint. Without it the hydrator will not work!
Other than that, the usage is straight-forward.

```php
<?php
$repo = $em->getRepository(Category::class);

$tree = $repo->createQueryBuilder('node')->getQuery()
    ->setHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true)
    ->getResult('tree');
```

## Advanced examples:

### Nesting Translatable and Sluggable extensions

If you want to attach **TranslatableListener** and also add it to EventManager after
the **SluggableListener** and **TreeListener**. It is important because slug must be generated first
before the creation of it`s translation.

```php
<?php
$evm = new \Doctrine\Common\EventManager();
$treeListener = new \Gedmo\Tree\TreeListener();
$evm->addEventSubscriber($treeListener);
$sluggableListener = new \Gedmo\Sluggable\SluggableListener();
$evm->addEventSubscriber($sluggableListener);
$translatableListener = new \Gedmo\Translatable\TranslatableListener();
$translatableListener->setTranslatableLocale('en_us');
$evm->addEventSubscriber($translatableListener);
// now this event manager should be passed to entity manager constructor
```

And the Entity should look like:

```php
<?php
namespace Entity;

use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
class Category
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @Gedmo\Sluggable
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\Sluggable]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private $lvl;

    /**
     * @var self|null
     *
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $root;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $parent;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'parent')]
    private $children;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @Gedmo\Slug
     * @ORM\Column(name="slug", type="string", length=128)
     */
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 128)]
    #[Gedmo\Translatable]
    #[Gedmo\Slug]
    private $slug;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setParent(self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
```

**Note:** If you use dql without object hydration, the nodes will not be
translated, because the postLoad event never will be triggered

Now the generated treenode slug will be translated by Translatable behavior.

It's as easy as that. Any suggestions on improvements are very welcome.

<a name="materialized-path"></a>

## Materialized Path

### Important notes before defining the schema

- If you use MongoDB you should activate the locking mechanism provided to avoid inconsistencies in cases where concurrent
modifications on the tree could occur. Look at the MongoDB example of schema definition to see how it must be configured.
- If your **TreePathSource** field is of type "string", then the primary key will be concatenated in the form: "value-id".
 This is to allow you to use non-unique values as the path source. For example, this could be very useful if you need to
 use the date as the path source (maybe to create a tree of comments and order them by date). If you want to change this
 default behaviour you can set the attribute "appendId" of **TreePath** to true or false. By default the path does not start
 with the given separator but ends with it. You can customize this behaviour with "startsWithSeparator" and "endsWithSeparator".
 `@Gedmo\TreePath(appendId=false, startsWithSeparator=true, endsWithSeparator=false)`
- **TreePath** field can only be of types: string, text
- **TreePathSource** field can only be of types: id, integer, smallint, bigint, string, int, float (I include here all the
variations of the field types, including the ORM and ODM for MongoDB ones).
- **TreeLockTime** must be of type "date" (used only in MongoDB for now).
- **TreePathHash** allows you to define a field that is automatically filled with the md5 hash of the path. This field could be necessary if you want to set a unique constraint on the database table.

### ORM Entity example (Annotations)

```php
<?php

namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\TreePath
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    private $path;

    /**
     * @Gedmo\TreePathSource
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    private $level;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $children;

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

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }
}

```

### MongoDB example (Annotations)

```php
<?php

namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MONGO;

/**
 * @MONGO\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
class Category
{
    /**
     * @MONGO\Id
     */
    private $id;

    /**
     * @MONGO\Field(type="string")
     * @Gedmo\TreePathSource
     */
    private $title;

    /**
     * @MONGO\Field(type="string")
     * @Gedmo\TreePath(separator="|")
     */
    private $path;

    /**
     * @Gedmo\TreeParent
     * @MONGO\ReferenceOne(targetDocument="Category")
     */
    private $parent;

    /**
     * @Gedmo\TreeLevel
     * @MONGO\Field(type="int")
     */
    private $level;

    /**
     * @Gedmo\TreeLockTime
     * @MONGO\Field(type="date")
     */
    private $lockTime;

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

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }
}

```

### Path generation

When an entity is inserted, a path is generated using the value of the field configured as the TreePathSource.
For example:

```php
$food = new Category();
$food->setTitle('Food');

$em->persist($food);
$em->flush();

// This would print "Food-1" assuming the id is 1.
echo $food->getPath();

$fruits = new Category();
$fruits->setTitle('Fruits');
$fruits->setParent($food);

$em->persist($fruits);
$em->flush();

// This would print "Food-1,Fruits-2" assuming that $food id is 1,
// $fruits id is 2 and separator = "," (the default value)
echo $fruits->getPath();

```

### Locking mechanism for MongoDB

Why do we need a locking mechanism for MongoDB? Sadly, MongoDB lacks full transactional support, so if two or more
users try to modify the same tree concurrently, it could lead to an inconsistent tree. So we've implemented a simple
locking mechanism to avoid this type of problems. It works like this: As soon as a user tries to modify a node of a tree,
it first check if the root node is locked (or if the current lock has expired).

If it is locked, then it throws an exception of type "Gedmo\Exception\TreeLockingException". If it's not locked,
it locks the tree and proceeds with the modification. After all the modifications are done, the lock is freed.

If, for some reason, the lock couldn't get freed, there's a lock timeout configured with a default time of 3 seconds.
You can change this value using the **lockingTimeout** parameter under the Tree attribute (or equivalent in annotation and XML).
You must pass a value in seconds to this parameter.


<a name="closure-table"></a>

## Closure Table

To be able to use this strategy, you'll need an additional entity which represents the closures. We already provide you an abstract
entity, so you need to extend from it and add mapping information for ancestor and descendant.

### Closure Entity

```php
<?php

namespace YourNamespace\Entity;

use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\UniqueConstraint(name="closure_unique_idx", columns={"ancestor", "descendant"})
 * @ORM\Index(name="closure_depth_idx", columns={"depth"})
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'closure_unique_idx', columns: ['ancestor', 'descendant'])]
#[ORM\Index(name: 'closure_depth_idx', columns: ['depth'])]
class CategoryClosure extends AbstractClosure
{
    /**
     * @ORM\ManyToOne(targetEntity="YourNamespace\Entity\Category")
     * @ORM\JoinColumn(name="ancestor", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'ancestor', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $ancestor;

    /**
     * @ORM\ManyToOne(targetEntity="YourNamespace\Entity\Category")
     * @ORM\JoinColumn(name="descendant", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'descendant', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $descendant;
}
```

Next step, define your entity.

### ORM Entity example (Annotations)

```php
<?php

namespace YourNamespace\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="YourNamespace\Entity\CategoryClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class Category
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * This parameter is optional for the closure strategy
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     * @Gedmo\TreeLevel
     */
    private $level;

    /**
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $children;

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

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function addClosure(CategoryClosure $closure)
    {
        $this->closures[] = $closure;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }
}

```

And that's it!


<a name="repository-methods"></a>

## Repository Methods (All strategies)

There are repository methods that are available for you in all the strategies:

* **getRootNodes** / **getRootNodesQuery** / **getRootNodesQueryBuilder**: Returns an array with the available root nodes. Arguments:
  - *sortByField*: An optional field to order the root nodes. Defaults to "null".
  - *direction*: In case the first argument is used, you can pass the direction here: "asc" or "desc". Defaults to "asc".
* **getChildren** / **getChildrenQuery** / **getChildrenQueryBuilder**: Returns an array of children nodes. Arguments:
  - *node*: If you pass a node, the method will return its children. Defaults to "null" (this means it will return ALL nodes).
  - *direct*: If you pass true as a value for this argument, you'll get only the direct children of the node
  (or only the root nodes if you pass "null" to the "node" argument).
  - *sortByField*: An optional field to sort the children. Defaults to "null".
  - *direction*: If you use the "sortByField" argument, this allows you to set the direction: "asc" or "desc". Defaults to "asc".
  - *includeNode*: Using "true", this argument allows you to include in the result the node you passed as the first argument. Defaults to "false".
* **childrenHierarchy**: This useful method allows you to build an array of nodes representing the hierarchy of a tree. Arguments:
  - *node*: If you pass a node, the method will return its children. Defaults to "null" (this means it will return ALL nodes).
  - *direct*: If you pass true as a value for this argument, you'll get only the direct children of the node
  - *options*: An array of options that allows you to decorate the results with HTML. Available options:
      * decorate: boolean (false) - retrieves tree as UL->LI tree
      * nodeDecorator: Closure (null) - uses $node as argument and returns decorated item as string
      * rootOpen: string || Closure ('\<ul\>') - branch start, closure will be given $children as a parameter
      * rootClose: string ('\</ul\>') - branch close
      * childOpen: string || Closure ('\<li\>') - start of node, closure will be given $node as a parameter
      * childClose: string ('\</li\>') - close of node
      * childSort: array || keys allowed: field: field to sort on, dir: direction. 'asc' or 'desc'
  - *includeNode*: Using "true", this argument allows you to include in the result the node you passed as the first argument. Defaults to "false".
* **setChildrenIndex** / **getChildrenIndex**: These methods allow you to change the default index used to hold the children when you use the **childrenHierarchy** method. Index defaults to "__children".

This list is not complete yet. We're working on including more methods in the common API offered by repositories of all the strategies.
Soon we'll be adding more helpful methods here.
