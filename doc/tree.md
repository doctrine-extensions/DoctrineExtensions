# Tree - Nestedset behavior extension for Doctrine 2

**Tree** nested behavior will implement the standard Nested-Set behavior
on your Entity. Tree supports different strategies and currently the alternative
to **nested-set** can be **closure-table** tree. Also this behavior can be nested 
with other extensions to translate or generated slugs of your tree nodes.

Features:

- Closure tree strategy, may be faster in some cases where ordering does not matter
- Support for multiple roots in nested-set
- No need for other managers, implementation is through event listener
- Synchronization of left, right values is automatic
- Can support concurrent flush with many objects being persisted and updated
- Can be nested with other extensions
- Annotation, Yaml and Xml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Thanks for contributions to:

- **[comfortablynumb](http://github.com/comfortablynumb) Gustavo Adrian** for Closure strategy
- **[everzet](http://github.com/everzet) Kudryashov Konstantin** for TreeLevel implementation
- **[stof](http://github.com/stof) Christophe Coevoet** for getTreeLeafs function

Update **2011-6-14**

- Tree extension now supports Materialized paths in MongoDB. The documentation is similar to that of
nested set and you have all the same method for inserting. Documentation is on the list todo still and
will be done within the next few weeks. See https://github.com/mtotheikle/Doctrine-MongoDB-Materlized-Path 
for a basic project that uses the new strategy, again documentation is limited with that as well and will
be expanded.

Update **2011-05-07**

- Tree is now able to act as **closure** tree, this strategy was refactored
and now fully functional. It is much faster for file-folder trees for instance
where you do not care about tree ordering.

Update **2011-04-11**

- Made in memory node synchronization, this change does not require clearing the cached nodes after any updates
to nodes, except **recover, verify and removeFromTree** operations.

Update **2011-02-08**

- Refactored to support multiple roots
- Changed the repository name, relevant to strategy used
- New [annotations](#annotations) were added


Update **2011-02-02**

- Refactored the Tree to the ability on supporting diferent tree models
- Changed the repository location in order to support future updates

**Notice:**

- You can [test live][blog_test] on this blog
- After using a NestedTreeRepository functions: **verify, recover, removeFromTree** it is recommended to clear EntityManager cache
because nodes may have changed values in database but not in memory. Flushing dirty nodes can lead to unexpected behaviour.
- Closure tree implementation is experimental and not fully functional, so far not documented either
- Public [Tree repository](http://github.com/l3pp4rd/DoctrineExtensions "Tree extension on Github") is available on github
- Last update date: **2011-06-08**

**Portability:**

- **Tree** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Tree** behavior

Content:
    
- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Tree Listener**
- Entity [example](#entity)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
- Basic usage [examples](#basic-examples)
- Advanced usage [examples](#advanced-examples)

## Setup and autoloading {#including-extension}

If you using the source from github repository, initial directory structure for 
the extension library should look like this:

    ...
    /DoctrineExtensions
        /lib
            /Gedmo
                /Exception
                /Loggable
                /Mapping
                /Sluggable
                /Timestampable
                /Translatable
                /Tree
        /tests
            ...
    ...

First of all we need to setup the autoloading of extensions:

    $classLoader = new \Doctrine\Common\ClassLoader('Gedmo', "/path/to/library/DoctrineExtensions/lib");
    $classLoader->register();

### Attaching the Tree Listener to the event manager {#event-listener}

To attach the **Tree Listener** to your event system:

    $evm = new \Doctrine\Common\EventManager();
    $treeListener = new \Gedmo\Tree\TreeListener();
    $evm->addEventSubscriber($treeListener);
    // now this event manager should be passed to entity manager constructor

## Tree Entity example: {#entity}

**Notice:** that Node interface is not necessary, except in cases there
you need to identify entity as being Tree Node. The metadata is loaded only once then
cache is activated

    namespace Entity;
    
    /**
     * @gedmo:Tree(type="nested")
     * @Table(name="categories")
     * use repository for handy tree functions
     * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
     */
    class Category
    {
        /**
         * @Column(name="id", type="integer")
         * @Id
         * @GeneratedValue
         */
        private $id;
    
        /**
         * @Column(name="title", type="string", length=64)
         */
        private $title;
    
        /**
         * @gedmo:TreeLeft
         * @Column(name="lft", type="integer")
         */
        private $lft;
        
        /**
         * @gedmo:TreeLevel
         * @Column(name="lvl", type="integer")
         */
        private $lvl;
        
        /**
         * @gedmo:TreeRight
         * @Column(name="rgt", type="integer")
         */
        private $rgt;
        
        /**
         * @gedmo:TreeRoot
         * @Column(name="root", type="integer", nullable=true)
         */
        private $root;
        
        /**
         * @gedmo:TreeParent
         * @ManyToOne(targetEntity="Category", inversedBy="children")
         * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
         */
        private $parent;
        
        /**
         * @OneToMany(targetEntity="Category", mappedBy="parent")
         * @OrderBy({"lft" = "ASC"})
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
        
        public function setParent(Category $parent)
        {
            $this->parent = $parent;    
        }
        
        public function getParent()
        {
            return $this->parent;   
        }
    }

### Tree annotations: {#annotations}

- **@gedmo:Tree(type="strategy")** this **class annotation** is used to set the tree strategy by **type** parameter.
Currently **nested** or **closure** strategy is supported
- **@gedmo:TreeLeft** it will use this field to store tree **left** value
- **@gedmo:TreeRight** it will use this field to store tree **right** value
- **@gedmo:TreeParent** this will identify this column as the relation to **parent node**
- **@gedmo:TreeLevel** it will use this field to store tree**level**
- **@gedmo:TreeRoot** it will use this field to store tree**root** id value

## Yaml mapping example {#yaml}

Yaml mapped Category: **/mapping/yaml/Entity.Category.dcm.yml**

    ---
    Entity\Category:
      type: entity
      table: categories
      gedmo:
        tree:
          type: nested
      id:
        id:
          type: integer
          generator:
            strategy: AUTO
      fields:
        title:
          type: string
          length: 64
        lft:
          type: integer
          gedmo:
            - treeLeft
        rgt:
          type: integer
          gedmo:
            - treeRight
        root:
          type: integer
          nullable: true
          gedmo:
            - treeRoot
        lvl:
          type: integer
          gedmo:
            - treeLevel
      manyToOne:
        parent:
          targetEntity: Entity\Category
          inversedBy: children
          joinColumn:
            name: parent_id
            referencedColumnName: id
            onDelete: SET NULL
          gedmo:
            - treeParent
      oneToMany:
        children:
          targetEntity: Entity\Category
          mappedBy: parent

## Xml mapping example {#xml}

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                      xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
    
        <entity name="Mapping\Fixture\Xml\NestedTree" table="nested_trees">
    
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
            <field name="root" type="integer">
                <gedmo:tree-root/>
            </field>
            <field name="level" column="lvl" type="integer">
                <gedmo:tree-level/>
            </field>
    
            <many-to-one field="parent" target-entity="NestedTree">
                <join-column name="parent_id" referenced-column-name="id" on-delete="SET_NULL"/>
                <gedmo:tree-parent/>
            </many-to-one>
    
            <gedmo:tree type="nested"/>
    
        </entity>
    
    </doctrine-mapping>

## Basic usage examples: {#basic-examples}

### To save some **Categories** and generate tree:

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

The result after flush will generate the food tree:

    /food (1-8)
        /fruits (2-3)
        /vegetables (4-7)
            /carrots (5-6)

### Using repository functions

    $repo = $em->getRepository('Entity\Category');
    
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
    $em->clear(); // clear cached nodes
    // if tree has errors it will try to fix all tree nodes
    
    // single node removal
    $vegies = $repo->findOneByTitle('Vegitables');
    $repo->removeFromTree($vegies);
    $em->clear(); // clear cached nodes
    // it will remove this node from tree and reparent all children
    
    // reordering the tree
    $food = $repo->findOneByTitle('Food');
    $repo->reorder($food, 'title');
    // it will reorder all "Food" tree node left-right values by the title

### Inserting node in different positions

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
        ->persistAsLastChildOf($vegitables, $food)
        ->persistAsNextSiblingOf($carrots, $fruits);
    
    $em->flush();

For more details you can check the **NestedTreeRepository** __call function

Moving up and down the nodes in same level:

Tree example:

    /Food
        /Vegitables
            /Onions
            /Carrots
            /Cabbages
            /Potatoes
        /Fruits

    $repo = $em->getRepository('Entity\Category');
    $carrots = $repo->findOneByTitle('Carrots');
    // move it up by one position
    $repo->moveUp($carrots, 1);

Tree after moving the Carrots up:

    /Food
        /Vegitables
            /Carrots &lt;- moved up
            /Onions
            /Cabbages
            /Potatoes
        /Fruits

    $repo = $em->getRepository('Entity\Category');
    $carrots = $repo->findOneByTitle('Carrots');
    // move it down to the end
    $repo->moveDown($carrots, true);

Tree after moving the Carrots down:

    /Food
        /Vegitables
            /Onions
            /Cabbages
            /Potatoes
            /Carrots &lt;- moved down to the end
        /Fruits

**Notice:** tree repository functions: **verify, recover, removeFromTree**. 
Will require to clear the cache of Entity Manager because left-right values will differ.
So after that use `$em->clear();` if you will continue using the nodes after these operations.

### If you need a repository for your TreeNode Entity simply extend it

    namespace Entity\Repository;
    
    use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
        
    class CategoryRepository extends NestedTreeRepository
    {
        // your code here
    }
    
    // and then on your entity link to this repository
    
    /**
     * @gedmo:Tree(type="nested")
     * @Entity(repositoryClass="Entity\Repository\CategoryRepository")
     */
    class Category implements Node
    {
        //...
    }

## Advanced examples: {#advanced-examples}

### Nesting Translatatable and Sluggable extensions

If you want to attach **TranslationListener** also add it to EventManager after
the **SluggableListener** and **TreeListener**. It is important because slug must be generated first
before the creation of it`s translation.

    $evm = new \Doctrine\Common\EventManager();
    $treeListener = new \Gedmo\Tree\TreeListener();
    $evm->addEventSubscriber($treeListener);
    $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
    $evm->addEventSubscriber($sluggableListener);
    $translatableListener = new \Gedmo\Translatable\TranslationListener();
    $translatableListener->setTranslatableLocale('en_us');
    $evm->addEventSubscriber($translatableListener);
    // now this event manager should be passed to entity manager constructor

And the Entity should look like:

    namespace Entity;
    
    /**
     * @gedmo:Tree(type="nested")
     * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
     */
    class Category
    {
        /**
         * @Column(name="id", type="integer")
         * @Id
         * @GeneratedValue
         */
        private $id;
    
        /**
         * @gedmo:Translatable
         * @gedmo:Sluggable
         * @Column(name="title", type="string", length=64)
         */
        private $title;
    
        /**
         * @gedmo:TreeLeft
         * @Column(name="lft", type="integer")
         */
        private $lft;
        
        /**
         * @gedmo:TreeRight
         * @Column(name="rgt", type="integer")
         */
        private $rgt;
        
        /**
         * @gedmo:TreeLevel
         * @Column(name="lvl", type="integer")
         */
        private $lvl;
        
        /**
         * @gedmo:TreeParent
         * @ManyToOne(targetEntity="Category", inversedBy="children")
         * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
         */
        private $parent;
        
        /**
         * @OneToMany(targetEntity="Category", mappedBy="parent")
         */
        private $children;
        
        /**
         * @gedmo:Translatable
         * @gedmo:Slug
         * @Column(name="slug", type="string", length=128)
         */
        private $slug;
    
        public function getId()
        {
            return $this->id;
        }
        
        public function getSlug()
        {
            return $this->slug;
        }
        
        public function setTitle($title)
        {
            $this->title = $title;
        }
    
        public function getTitle()
        {
            return $this->title;
        }
        
        public function setParent(Category $parent)
        {
            $this->parent = $parent;    
        }
        
        public function getParent()
        {
            return $this->parent;   
        }
    }

Yaml mapped Category: **/mapping/yaml/Entity.Category.dcm.yml**

    ---
    Entity\Category:
      type: entity
      table: categories
      gedmo:
        tree:
          type: nested
      id:
        id:
          type: integer
          generator:
            strategy: AUTO
      fields:
        title:
          type: string
          length: 64
          gedmo:
            - translatable
            - sluggable
        lft:
          type: integer
          gedmo:
            - treeLeft
        rgt:
          type: integer
          gedmo:
            - treeRight
        lvl:
          type: integer
          gedmo:
            - treeLevel
        slug:
          type: string
          length: 128
          gedmo:
            - translatable
            - slug
      manyToOne:
        parent:
          targetEntity: Entity\Category
          inversedBy: children
          joinColumn:
            name: parent_id
            referencedColumnName: id
            onDelete: SET NULL
          gedmo:
            - treeParent
      oneToMany:
        children:
          targetEntity: Entity\Category
          mappedBy: parent

**Notice:** that using dql without object hydration, the nodes will not be
translated. Because the postLoad event never will be triggered

Now the generated treenode slug will be translated by Translatable behavior

Easy like that, any suggestions on improvements are very welcome
