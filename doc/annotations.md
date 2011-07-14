# Annotation reference

Bellow you will find all annotation descriptions used in these extensions.
There will be introduction on usage with examples. For more detailed usage
on extensions, refer to their specific documentation.

Content:

- [New annotation mapping](#annotation_mapping) example for common3.0.x
- Best [practices](#setup) for setting up
- [Tree](#tree)
- [Translatable](#translatable)
- [Sluggable](#sluggable)
- [Timestampable](#timestampable)
- [Loggable](#loggable)

## New annotation mapping example for common3.0.x {#annotation_mapping}

Recently there was an upgrade made for annotation reader in order to support
more native way for annotation mapping in **common3.0.x** branch. Before that
you had to make aliases for namespaces (like __gedmo:Translatable__), this strategy
was limited and errors were not explainable. Now you have to add a **use** statement
for each annotation you use in your mapping, see example bellow:

    namespace MyApp\Entity;
    
    use Gedmo\Mapping\Annotation as Gedmo; // this will be like an alias before
    use Doctrine\ORM\Mapping\Id; // includes single annotation
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * @ORM\Entity
     * @Gedmo\TranslationEntity(class="something")
     */
    class Article
    {
        /**
         * @Id
         * @ORM\GeneratedValue
         * @ORM\Column(type="integer")
         */
        private $id;
        
        /**
         * @Gedmo\Translatable
         * @Gedmo\Sluggable
         * @ORM\Column(length=64)
         */
        private $title;
        
        /**
         * @Gedmo\Slug
         * @ORM\Column(length=64, unique=true)
         */
        private $slug;
    }

**Note:** this new mapping applies only if you use **doctrine-common** library at version **3.0.x**

## Best practices for setting up with annotations {#setup}

New annotation reader does not depend on any namespaces, for that reason you can use
single reader instance for whole project. The example bellow shows how to setup the
mapping and listeners:


    $reader = new \Doctrine\Common\Annotations\AnnotationReader();
    $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
    
    $chain = new \Doctrine\ORM\Mapping\Driver\DriverChain;
    $chain->addDriver($annotationDriver, 'Gedmo\Translatable\Entity');
    $chain->addDriver($annotationDriver, 'Gedmo\Tree\Entity');
    
    $config = new \Doctrine\ORM\Configuration();
    $config->setMetadataDriverImpl($chain);
    $config->setProxyDir(/*location*/);
    $config->setProxyNamespace('Proxy');
    $config->setAutoGenerateProxyClasses(false);
    
    $evm = new \Doctrine\Common\EventManager();
    
    $translatable = new \Gedmo\Translatable\TranslationListener();
    $translatable->setAnnotationReader($reader);
    $evm->addEventSubscriber($translatable);
    
    $tree = new \Gedmo\Tree\TreeListener;
    $tree->setAnnotationReader($reader);
    $evm->addEventSubscriber($tree);
    
    //...
    $conn = array(
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'test',
        'user' => 'root',
        'password' => ''
    );
    $em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

**Notice:** that symfony2 DoctrineExtensionsBundle does it automatically this
way you will maintain a single instance of annotation reader. It relates only
to doctrine-common-3.0.x branch.

## Tree annotations {#tree}

Tree can use diferent adapters. Currently **Tree** extension supports **NestedSet**
and **Closure** strategies which has a difference for annotations used. Note, that 
tree will automatically map indexes which are considered necessary for best performance.

### @gedmo:Tree (required for all tree strategies)

**class** annotation

Is the main identificator of tree used for domain object which should **act as Tree**.

**options:**

- **type** - (string) _optional_ default: **nested**

example:

    /**
     * @gedmo:Tree(type="nested")
     * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
     */
    class Category ...

### @gedmo:TreeParent (required for all tree strategies)

**property** annotation

This annotation forces to specify the **parent** field, which must be a **ManyToOne**
relation

example:

    /**
     * @gedmo:TreeParent
     * @ManyToOne(targetEntity="Category")
     * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $parent;

### @gedmo:TreeLeft (required for nested tree)

**property** annotation

This annotation forces to specify the **left** field, which will be used for generation
of nestedset left values. Property must be **integer** type.

example:

    /**
     * @gedmo:TreeLeft
     * @Column(type=integer)
     */
    private $lft;

### @gedmo:TreeRight (required for nested tree)

**property** annotation

This annotation forces to specify the **right** field, which will be used for generation
of nestedset right values. Property must be **integer** type.

example:

    /**
     * @gedmo:TreeRight
     * @Column(type=integer)
     */
    private $rgt;

### @gedmo:TreeRoot (optional for nested tree)

**property** annotation

This annotation will use **integer** type field to specify the root of tree. This way
updating tree will cost less because each root will act as separate tree.

example:

    /**
     * @gedmo:TreeRoot
     * @Column(type=integer, nullable=true)
     */
    private $root;

### @gedmo:TreeLevel (optional for nested tree)

**property** annotation

This annotation lets to store the **level** of each node in the tree, in other word it
is depth. Can be used for indentation for instance. Property must be **integer** type.

example:

    /**
     * @gedmo:TreeLevel
     * @Column(type=integer)
     */
    private $lvl;

### @gedmo:TreeClosure (required for closure tree)

**class** annotation

This annotation forces to specify the closure domain object, which must
extend **AbstractClosure** in order to have personal closures.

**options:**

- **class** - (string) _required_

example:

    /**
     * @gedmo:Tree(type="closure")
     * @gedmo:TreeClosure(class="Entity\CategoryClosure")
     * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
     */
    class Category ...

## Translatable annotations {#translatable}

Translatable additionaly can have unmapped property, which would override the
locale used by listener.

### @gedmo:TranslationEntity (optional)

**class** annotation

This class annotation can force translatable to use **personal Entity** to store
translations. In large tables this can be very handy.

**options:**

- **class** - (string) _required_

example:

    /**
     * @gedmo:TranslationEntity(class="Entity\ProductTranslation")
     * @Entity
     */
    class Product ...

### @gedmo:Translatable (required in order to translate)

**property** annotation

This annotation simply marks **any type** of field to be tracked and translated into
currently used locale. Locale can be forced through entity or set by **TranslationListener**.

example:

    /**
     * @gedmo:Translatable
     * @Column(type=text)
     */
    private $content;
    
### @gedmo:Locale or @gedmo:Language (optional)

**unmapped property** annotation

Both annotations will do exactly the same - mark property as one which can override
the locale set by **TranslationListener**. Property must not be mapped, that means
it cannot be stored in database.

example:

    /**
     * @gedmo:Locale
     */
    private $locale;

## Sluggable annotations {#sluggable}

Sluggable ensures unique slugs and correct length of the slug. It also uses utf8 to ascii
table map to create correct ascii slugs.

### @gedmo:Sluggable (required at least one sluggable field)

**property** annotation

Includes the marked **string** type property into generation of slug.
Additionaly can use **position** option to set field position is slug

**options:**

- **position** - (integer) _optional_
- **slugField** - (string) _optional_ default: **slug**

example:

    /**
     * @gedmo:Sluggable(slugField="slug")
     * @Column(length=64)
     */
    private $code;

### @gedmo:Slug (required)

**property** annotation

It will use this **string** property to store the generated slug.

**options:**

- **updatable** - (boolean) _optional_ default: **true**
- **separator** - (string) _optional_ default: **-**
- **unique** - (boolean) _optional_ default: **true**
- **style** - (string) _optional_ default: **default** lowercase, can be **camel** also

example:

    /**
     * @gedmo:Slug
     * @Column(length=64, unique=true)
     */
    private $slug;

## Timestampable annotations {#timestampable}

Timestampable will update date fields on create, update or property change. If you set/force
date manualy it will not update it.

### @gedmo:Timestampable (required)

**property** annotation

Marks a **date, datetime or time** field as timestampable.

**options:**

- **on** - (string) _optional_ default: **update**, other choise is **create** or **change**
- **field** - (string) _conditional_ required only if it triggers on **change**, name of the **field**
or if it is a relation **property.field**
- **value** - (mixed) _conditional_ required only if it triggers on **change**, value of property
which would trigger an update.

example:

    /**
     * @gedmo:Timestampable(on="create")
     * @Column(type="datetime")
     */
    private $created;
    
    /**
     * @gedmo:Timestampable(on="change", field="status.title", value="Published")
     * @Column(type="date")
     */
    private $published;
    
    /**
     * @ManyToOne(targetEntity="Status")
     */
    private $status;

## Loggable annotations {#loggable}

Loggable is used to log all actions made on annotated object class, it logs insert, update
and remove actions for a username which currently is logged in for instance.
Further more, it stores all **Versioned** property changes in the log which allows
a version management implementation for this object.

### @gedmo:Loggable (required)

**class** annotation

This class annotation marks object as being loggable and logs all actions being done to
this class records.

**options:**

- **logEntryClass** - (string) _optional_ personal log storage class

example:

    /**
     * @gedmo:Loggable(logEntryClass="Entity\ProductLogEntry")
     * @Entity
     */
    class Product ...

### @gedmo:Versioned (optional)

**property** annotation

Tracks the marked property for changes to be logged, can be set to single valued associations
but not for collections. Using these log entries as revisions, objects can be reverted to
a specific version.

example:

    /**
     * @gedmo:Versioned
     * @Column(type="text")
     */
    private $content;
    
    /**
     * @gedmo:Versioned
     * @ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    private $article;

