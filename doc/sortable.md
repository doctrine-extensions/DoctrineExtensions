# Sortable behavior extension for Doctrine 2

**Sortable** behavior will maintain a position field for ordering
entities.

Features:

- Automatic handling of position index
- Group entity ordering by one or more fields
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions

**Notice:**

- Public [Sortable repository](http://github.com/l3pp4rd/DoctrineExtensions "Sortable extension on Github") is available on github
- Last update date: **2011-06-08**

**Portability:**

- **Sortable** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Sortable**
behavior

Content:

- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Sortable Listener**
- Entity [example](#entity)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
- Basic usage [examples](#basic-examples)


## Setup and autoloading {#including-extension}

If you are using the source from github repository, initial directory structure for
the extension library should look like this:

    ...
    /DoctrineExtensions
        /lib
            /Gedmo
                /Exception
                /Loggable
                /Mapping
                /Sluggable
                /Sortable
                /Timestampable
                /Translatable
                /Tree
        /tests
            ...
    ...

First of all we need to setup the autoloading of extensions:

    $classLoader = new \Doctrine\Common\ClassLoader('Gedmo', "/path/to/library/DoctrineExtensions/lib");
    $classLoader->register();

### Attaching the Sortable Listener to the event manager {#event-listener}

To attach the **Sortable Listener** to your event system:

    $evm = new \Doctrine\Common\EventManager();
    // ORM
    $sortableListener = new \Gedmo\Sortable\SortableListener();
    
    $evm->addEventSubscriber($sortableListener);
    // now this event manager should be passed to entity manager constructor

## Sortable Entity example: {#entity}

### Sortable annotations:

- **@gedmo:SortableGroup** it will use this field for **grouping**
- **@gedmo:SortablePosition** it will use this column to store **position** index

**Notice:** that Sortable interface is not necessary, except in cases there
you need to identify entity as being Sortable. The metadata is loaded only once then
cache is activated

    namespace Entity;
    
    /**
     * @Table(name="items")
     * @Entity
     */
    class Item
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @Column(name="name", type="string", length=64)
         */
        private $name;
    
        /**
         * @Gedmo\SortablePosition
         * @Column(name="position", type="integer")
         */
        private $position;
    
        /**
         * @Gedmo\SortableGroup
         * @Column(name="category", type="string", length=128)
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


## Yaml mapping example {#yaml}

Yaml mapped Item: **/mapping/yaml/Entity.Item.dcm.yml**

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

## Xml mapping example {#xml}

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

## Basic usage examples: {#basic-examples}

### To save **Items** at the end of the sorting list simply do:

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

### Save **Item** at a given position:

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
    $this->em->clear();
    
    $repo = $this->em->getRepository('Entity\\Item');
    $items = $repo->getBySortableGroupsQuery(array('category' => 'category 1'));
    foreach ($items as $item) {
        echo "{$item->getPosition()}: {$item->getName()}\n";
    }
    // prints:
    // 0: item 0
    // 1: item 1
    // 2: item 2
    
    
### Reordering the sorted list:

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
    
    $this->em->clear();
    
    $repo = $this->em->getRepository('Entity\\Item');
    $items = $repo->getBySortableGroupsQuery(array('category' => 'category 1'));
    foreach ($items as $item) {
        echo "{$item->getPosition()}: {$item->getName()}\n";
    }
    // prints:
    // 0: item 2
    // 1: item 1
