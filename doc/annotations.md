# Annotation reference

Bellow you will find all annotation descriptions used in these extensions.
There will be introduction on usage with examples. For more detailed usage
on extensions, refer to their specific documentation.

Content:

- Best [practices](#em-setup) for setting up
- [Tree](#gedmo-tree)
- [Translatable](#gedmo-translatable)
- [Sluggable](#gedmo-sluggable)
- [Timestampable](#gedmo-timestampable)
- [Loggable](#gedmo-loggable)

## Annotation mapping

Starting from **doctrine2.1.x** versions you have to import all used annotations
by an **use** statement, see example bellow:

``` php
namespace MyApp\Entity;

use Gedmo\Mapping\Annotation as Gedmo; // this will be like an alias for Gedmo extensions annotations
use Doctrine\ORM\Mapping\Id; // includes single annotation
use Doctrine\ORM\Mapping as ORM; // alias for doctrine ORM annotations

/**
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="something")
 */
class Article
{
    /**
     * @Id
     * @Gedmo\Slug(fields={"title"}, updatable=false, separator="_")
     * @ORM\Column(length=32, unique=true)
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;
}
```

**Note:** this mapping applies only if you use **doctrine-common** library at version **2.1.x** or higher,
extension library still supports old mapping styles if you manually set the mapping drivers

<a name="em-setup"></a>

## Best practices for setting up with annotations

New annotation reader does not depend on any namespaces, for that reason you can use
single reader instance for whole project. The example bellow shows how to setup the
mapping and listeners:

**Note:** using this repository you can test and check the [example demo configuration](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/example/em.php)

``` php
<?php
// WARNING: setup, assumes that autoloaders are set

// globally used cache driver, in production use APC or memcached
$cache = new Doctrine\Common\Cache\ArrayCache;
// standard annotation reader
$annotationReader = new Doctrine\Common\Annotations\AnnotationReader;
$cachedAnnotationReader = new Doctrine\Common\Annotations\CachedReader(
    $annotationReader, // use reader
    $cache // and a cache driver
);
// create a driver chain for metadata reading
$driverChain = new Doctrine\ORM\Mapping\Driver\DriverChain();
// load superclass metadata mapping only, into driver chain
// also registers Gedmo annotations.NOTE: you can personalize it
Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
    $driverChain, // our metadata driver chain, to hook into
    $cachedAnnotationReader // our cached annotation reader
);

// now we want to register our application entities,
// for that we need another metadata driver used for Entity namespace
$annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    $cachedAnnotationReader, // our cached annotation reader
    array(__DIR__.'/app/Entity') // paths to look in
);
// NOTE: driver for application Entity can be different, Yaml, Xml or whatever
// register annotation driver for our application Entity namespace
$driverChain->addDriver($annotationDriver, 'Entity');

// general ORM configuration
$config = new Doctrine\ORM\Configuration;
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false); // this can be based on production config.
// register metadata driver
$config->setMetadataDriverImpl($driverChain);
// use our already initialized cache driver
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// create event manager and hook preferred extension listeners
$evm = new Doctrine\Common\EventManager();
// gedmo extension listeners, remove which are not used

// sluggable
$sluggableListener = new Gedmo\Sluggable\SluggableListener;
// you should set the used annotation reader to listener, to avoid creating new one for mapping drivers
$sluggableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($sluggableListener);

// tree
$treeListener = new Gedmo\Tree\TreeListener;
$treeListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($treeListener);

// loggable, not used in example
$loggableListener = new Gedmo\Loggable\LoggableListener;
$loggableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($loggableListener);

// timestampable
$timestampableListener = new Gedmo\Timestampable\TimestampableListener;
$timestampableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($timestampableListener);

// translatable
$translatableListener = new Gedmo\Translatable\TranslatableListener;
// current translation locale should be set from session or hook later into the listener
// most important, before entity manager is flushed
$translatableListener->setTranslatableLocale('en');
$translatableListener->setDefaultLocale('en');
$translatableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($translatableListener);

// sortable, not used in example
$sortableListener = new Gedmo\Sortable\SortableListener;
$sortableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($sortableListener);

// mysql set names UTF-8 if required
$evm->addEventSubscriber(new Doctrine\DBAL\Event\Listeners\MysqlSessionInit());
// DBAL connection
$connection = array(
    'driver' => 'pdo_mysql',
    'host' => '127.0.0.1',
    'dbname' => 'test',
    'user' => 'root',
    'password' => ''
);
// Finally, create entity manager
$em = Doctrine\ORM\EntityManager::create($connection, $config, $evm);
```

**Note:** that symfony2 StofDoctrineExtensionsBundle does it automatically this
way you will maintain a single instance of annotation reader. It relates only
to doctrine-common-2.1.x branch and newer.

<a name="gedmo-tree"></a>

## Tree annotations

Tree can use different adapters. Currently **Tree** extension supports **NestedSet**
and **Closure** strategies which has a difference for annotations used. Note, that
tree will automatically map indexes which are considered necessary for best performance.

### @Gedmo\Mapping\Annotation\Tree (required for all tree strategies)

**class** annotation

Is the main identificator of tree used for domain object which should **act as Tree**.

**options:**

- **type** - (string) _optional_ default: **nested**

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Tree(type="nested")
 * @Doctrine\ORM\Mapping\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Category ...
```

### @Gedmo\Mapping\Annotation\TreeParent (required for all tree strategies)

**property** annotation

This annotation forces to specify the **parent** field, which must be a **ManyToOne**
relation

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TreeParent
 * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Category")
 * @Doctrine\ORM\Mapping\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
 */
private $parent;
```

### @Gedmo\Mapping\Annotation\TreeLeft (required for nested tree)

**property** annotation

This annotation forces to specify the **left** field, which will be used for generation
of nestedset left values. Property must be **integer** type.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TreeLeft
 * @Doctrine\ORM\Mapping\Column(type=integer)
 */
private $lft;
```

### @Gedmo\Mapping\Annotation\TreeRight (required for nested tree)

**property** annotation

This annotation forces to specify the **right** field, which will be used for generation
of nestedset right values. Property must be **integer** type.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TreeRight
 * @Doctrine\ORM\Mapping\Column(type=integer)
 */
private $rgt;
```

### @Gedmo\Mapping\Annotation\TreeRoot (optional for nested tree)

**property** annotation

This annotation will use **integer** type field to specify the root of tree. This way
updating tree will cost less because each root will act as separate tree.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TreeRoot
 * @Doctrine\ORM\Mapping\Column(type=integer, nullable=true)
 */
private $root;
```

### @Gedmo\Mapping\Annotation\TreeLevel (optional for nested tree)

**property** annotation

This annotation lets to store the **level** of each node in the tree, in other word it
is depth. Can be used for indentation for instance. Property must be **integer** type.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TreeLevel
 * @Doctrine\ORM\Mapping\Column(type=integer)
 */
private $lvl;
```

### @Gedmo\Mapping\Annotation\TreeClosure (required for closure tree)

**class** annotation

This annotation forces to specify the closure domain object, which must
extend **AbstractClosure** in order to have personal closures.

**options:**

- **class** - (string) _required_

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Tree(type="closure")
 * @Gedmo\Mapping\Annotation\TreeClosure(class="Entity\CategoryClosure")
 * @Doctrine\ORM\Mapping\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class Category ...
```

<a name="gedmo-translatable"></a>

## Translatable annotations

Translatable additionally can have unmapped property, which would override the
locale used by listener.

### @Gedmo\Mapping\Annotation\TranslationEntity (optional)

**class** annotation

This class annotation can force translatable to use **personal Entity** to store
translations. In large tables this can be very handy.

**options:**

- **class** - (string) _required_

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\TranslationEntity(class="Entity\ProductTranslation")
 * @Doctrine\ORM\Mapping\Entity
 */
class Product ...
```

### @Gedmo\Mapping\Annotation\Translatable (required in order to translate)

**property** annotation

This annotation simply marks **any type** of field to be tracked and translated into
currently used locale. Locale can be forced through entity or set by **TranslationListener**.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Translatable
 * @Doctrine\ORM\Mapping\Column(type=text)
 */
private $content;
```

### @Gedmo\Mapping\Annotation\Locale or @Gedmo\Mapping\Annotation\Language (optional)

**unmapped property** annotation

Both annotations will do exactly the same - mark property as one which can override
the locale set by **TranslationListener**. Property must not be mapped, that means
it cannot be stored in database.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Locale
 */
private $locale;
```

<a name="gedmo-sluggable"></a>

## Sluggable annotations

Sluggable ensures unique slugs and correct length of the slug. It also uses utf8 to ascii
table map to create correct ascii slugs.

### @Gedmo\Mapping\Annotation\Slug (required)

**property** annotation

It will use this **string** property to store the generated slug.

**options:**

- **fields** - (array) _required_, must at least contain one field
- **updatable** - (boolean) _optional_ default: **true**
- **separator** - (string) _optional_ default: **-**
- **unique** - (boolean) _optional_ default: **true**
- **style** - (string) _optional_ default: **default** lowercase, can be **camel** also
- **handlers** - (array) _optional_ default: empty array, refer to the documentation below, possible elements: **Gedmo\Mapping\Annotation\SlugHandler**

### Slug handlers:

- Gedmo\Sluggable\Handler\TreeSlugHandler - transforms a tree slug into path based, example "food/fruits/apricots/king-apricots"
- Gedmo\Sluggable\Handler\RelativeSlugHandler - takes a relation slug and prefixes the slug, example "singers/michael-jackson"
in order to synchronize updates regarding the relation changes, you will need to hood **InversedRelativeSlugHandler** to the relation mentioned.
- Gedmo\Sluggable\Handler\InversedRelativeSlugHandler - updates prefixed slug for an inversed relation which is mapped by **RelativeSlugHandler**

examples:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Slug
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
private $slug;
```

with TreeSlugHandler

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Slug(handlers={
 *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="parentRelationField", value="parent"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="separator", value="/")
 *      })
 * }, separator="-", updatable=true)
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
private $slug;
```

with **RelativeSlugHandler**:

``` php
<?php
/**
 * Person domain object class
 *
 * @Gedmo\Mapping\Annotation\Slug(handlers={
 *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationField", value="category"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationSlugField", value="slug"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="separator", value="/")
 *      })
 * })
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
private $slug;
```

if you used **RelativeSlugHandler** - relation object should use **InversedRelativeSlugHandler**:

``` php
<?php
/**
 * Category domain object class
 *
 * @Gedmo\Mapping\Annotation\Slug(handlers={
 *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationClass", value="App\Entity\Person"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="mappedBy", value="category"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="inverseSlugField", value="slug")
 *      })
 * })
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
private $slug;
```

<a name="gedmo-timestampable"></a>

## Timestampable annotations

Timestampable will update date fields on create, update or property change. If you set/force
date manually it will not update it.

### @Gedmo\Mapping\Annotation\Timestampable (required)

**property** annotation

Marks a **date, datetime or time** field as timestampable.

**options:**

- **on** - (string) _optional_ default: **update**, other choice is **create** or **change**
- **field** - (string) _conditional_ required only if it triggers on **change**, name of the **field**
or if it is a relation **property.field**
- **value** - (mixed) _conditional_ required only if it triggers on **change**, value of property
which would trigger an update.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Timestampable(on="create")
 * @Doctrine\ORM\Mapping\Column(type="datetime")
 */
private $created;

/**
 * @Gedmo\Mapping\Annotation\Timestampable(on="change", field="status.title", value="Published")
 * @Doctrine\ORM\Mapping\Column(type="date")
 */
private $published;

/**
 * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Status")
 */
private $status;
```

<a name="gedmo-loggable"></a>

## Loggable annotations

Loggable is used to log all actions made on annotated object class, it logs insert, update
and remove actions for a username which currently is logged in for instance.
Further more, it stores all **Versioned** property changes in the log which allows
a version management implementation for this object.

### @Gedmo\Mapping\Annotation\Loggable (required)

**class** annotation

This class annotation marks object as being loggable and logs all actions being done to
this class records.

**options:**

- **logEntryClass** - (string) _optional_ personal log storage class

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Entity\ProductLogEntry")
 * @Doctrine\ORM\Mapping\Entity
 */
class Product ...
```

### @Gedmo\Mapping\Annotation\Versioned (optional)

**property** annotation

Tracks the marked property for changes to be logged, can be set to single valued associations
but not for collections. Using these log entries as revisions, objects can be reverted to
a specific version.

example:

``` php
<?php
/**
 * @Gedmo\Mapping\Annotation\Versioned
 * @Doctrine\ORM\Mapping\Column(type="text")
 */
private $content;

/**
 * @Gedmo\Mapping\Annotation\Versioned
 * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Article", inversedBy="comments")
 */
private $article;
```

