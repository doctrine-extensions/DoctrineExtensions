# Mapping extension for Doctrine2

**Mapping** extension makes it easy to map additional metadata for event listeners.
It supports **Yaml**, **Xml** and **Annotation** drivers which will be chosen depending on
currently used mapping driver for your domain objects. **Mapping** extension also
provides abstraction layer of **EventArgs** to make it possible to use single listener
for different object managers like **ODM** and **ORM**.

Features:

- Mapping drivers for annotation and yaml
- Conventional extension points for metadata extraction and object manager abstraction

- Public [Mapping repository](http://github.com/l3pp4rd/DoctrineExtensions "Mapping extension on Github") is available on github
- Last update date: **2012-01-02**

This article will cover the basic installation and usage of **Mapping** extension

Content:

- [Including](#including-extension) the extension
- [Creating](#create-extension) an extension
- Defining [annotations](#annotations)
- Creating [listener](#create-listener)
- Attaching our [listener](#attach-listener) to the event manager
- [Entity](#entity-mapping) with some fields to encode
- Adapting listener to support [different](#different-managers) object managers
- [Customizing](#event-adapter-customize) event adapter for specific functions

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

<a name="create-extension"></a>

## Tutorial on creation of mapped extension

First, lets assume we will use **Extension** namespace for our additional
extension library. You should create an **Extension** directory in your library
or vendor directory. After some changes your project might look like:

```
project
    ...
    bootstrap.php
    vendor
        Extension
        ...
...
```

Now you can use any namespace autoloader class and register this namespace. We
will use Doctrine\Common\ClassLoader for instance:

``` php
<?php
// path is related to bootstrap.php location for example
$classLoader = new \Doctrine\Common\ClassLoader('Extension', "vendor");
$classLoader->register();
```

Now lets create some files which are necessary for our extension:

```
project
    ...
    bootstrap.php
    vendor
        Extension
            Encoder
                Mapping
                    Driver
                        Annotation.php
                    Annotations.php
                EncoderListener.php
...
```

**Note:** that extension will look for mapping in **ExtensionNamespace/Mapping**
directory. And **Driver** directory should be named as Driver. These are the conventions
of **Mapping** extension.

That is all we will need for now. As you may noticed we will create an encoding
listener which could encode your fields by specified annotations. In real life it
may not be useful since object will not know how to match the value.

<a name="annotations"></a>

## Now lets define available annotations and setup drivers

Edit **Annotations.php** file:

``` php
<?php
// file: vendor/Extension/Encoder/Mapping/Annotations.php

namespace Extension\Encoder\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Encode extends Annotation
{
    public $type = 'md5';
    public $secret;
}
```

Edit **Annotation.php** driver file:

``` php
<?php
// file: vendor/Extension/Encoder/Mapping/Driver/Annotation.php

namespace Extension\Encoder\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Doctrine\Common\Annotations\AnnotationReader;

class Annotation implements Driver
{
    public function readExtendedMetadata($meta, array &$config) {
        // load our available annotations
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();

        $class = $meta->getReflectionClass();
        // check only property annotations
        foreach ($class->getProperties() as $property) {
            // skip inherited properties
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // now lets check if property has our annotation
            if ($encode = $reader->getPropertyAnnotation($property, 'Extension\Encoder\Mapping\Encode')) {
                $field = $property->getName();
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new \Exception("Field is not mapped as object property");
                }
                // allow encoding only strings
                if (!in_array($encode->type, array('sha1', 'md5'))) {
                    throw new \Exception("Invalid encoding type supplied");
                }
                // validate encoding type
                $mapping = $meta->getFieldMapping($field);
                if ($mapping['type'] != 'string') {
                    throw new \Exception("Only strings can be encoded");
                }
                // store the metadata
                $config['encode'][$field] = array(
                    'type' => $encode->type,
                    'secret' => $encode->secret
                );
            }
        }
    }
}
```

<a name="create-listener"></a>

## Finally, lets create the listener

**Note:** this version of listener will support only ORM Entities

``` php
<?php
// file: vendor/Extension/Encoder/EncoderListener.php

namespace Extension\Encoder;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata'
        );
    }

    public function loadClassMetadata(EventArgs $args)
    {
        // this will check for our metadata
        $this->loadMetadataForObjectClass(
            $args->getEntityManager(),
            $args->getClassMetadata()
        );
    }

    public function onFlush(EventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // check all pending updates
        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $meta = $em->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($em, $meta->name)) {
                $this->encode($em, $object, $config);
            }
        }
        // check all pending insertions
        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $meta = $em->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($em, $meta->name)) {
                $this->encode($em, $object, $config);
            }
            // recalculate changeset
            $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $object);
        }
    }

    protected function getNamespace()
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode($em, $object, $config)
    {
        $meta = $em->getClassMetadata(get_class($object));
        foreach ($config['encode'] as $field => $options) {
            $value = $meta->getReflectionProperty($field)->getValue($object);
            $method = $options['type'];
            $encoded = $method($options['secret'].$value);
            $meta->getReflectionProperty($field)->setValue($object, $encoded);
        }
        // recalculate changeset
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $object);
    }
}
```

Our **Encoder** extension is ready, now if we want to test it, we need
to attach our **EncoderListener** to the EventManager and create an entity
with some fields to encode.

<a name="attach-listener"></a>

### Attaching the EncoderListener

``` php
<?php
$evm = new \Doctrine\Common\EventManager();
$encoderListener = new \Extension\Encoder\EncoderListener;
$evm->addEventSubscriber($encoderListener);
// now this event manager should be passed to entity manager constructor
```

<a name="entity-mapping"></a>

### Create an entity with some fields to encode

``` php
<?php
namespace YourNamespace\Entity;

use Doctrine\ORM\Mapping as ORM;
use Extension\Encoder\Mapping as EXT;

/**
 * @ORM\Table(name="test_users")
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @EXT\Encode(type="sha1", secret="xxx")
     * @ORM\Column(length=64)
     */
    private $name;

    /**
     * @EXT\Encode(type="md5")
     * @ORM\Column(length=32)
     */
    private $password;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
```

If you will try to create a new **User** you will get encoded fields in database.

<a name="different-managers"></a>

## Adapting listener to support other object managers

Now the event adapter comes into play, lets slightly modify our listener:

``` php
<?php
// file: vendor/Extension/Encoder/EncoderListener.php

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\Event\AdapterInterface as EventAdapterInterface;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata'
        );
    }

    public function loadClassMetadata(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        // this will check for our metadata
        $this->loadMetadataForObjectClass(
            $ea->getObjectManager(),
            $args->getClassMetadata()
        );
    }

    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // check all pending updates
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->encode($ea, $object, $config);
            }
        }
        // check all pending insertions
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->encode($ea, $object, $config);
            }
            // recalculate changeset
            $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
        }
    }

    protected function getNamespace()
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode(EventAdapterInterface $ea, $object, $config)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        foreach ($config['encode'] as $field => $options) {
            $value = $meta->getReflectionProperty($field)->getValue($object);
            $method = $options['type'];
            $encoded = $method($options['secret'].$value);
            $meta->getReflectionProperty($field)->setValue($object, $encoded);
        }
        // recalculate changeset
        $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
    }
}
```

**Note:** event adapter uses **EventArgs** to recognize with which manager
we are dealing with. It also uses event arguments to retrieve manager and transforms
the method call in its way. You can extend the event adapter in order to add some
specific methods for each manager.

That's it, now it will work on ORM and ODM object managers.

<a name="event-adapter-customize"></a>

## Customizing event adapter for specific functions

In most cases event listener will need specific functionality which will differ
for every object manager. For instance, a query to load users will differ. The
example bellow will illustrate how to handle such situations. You will need to
extend default ORM and ODM event adapters to implement specific functions which
will be available through the event adapter. First we will need to follow the
mapping convention to use those extension points.

### Extending default event adapters

Update your directory structure:

```
project
    ...
    bootstrap.php
    vendor
        Extension
            Encoder
                Mapping
                    Driver
                        Annotation.php
                    Event
                        Adapter
                            ORM.php
                            ODM.php
                    Annotations.php
                EncoderListener.php
...
```

Now **Mapping** extension will automatically create event adapter instances
from the extended ones.

Create extended ORM event adapter:

``` php
<?php
// file: vendor/Extension/Encoder/Mapping/Event/Adapter/ORM.php

namespace Extension\Encoder\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;

class ORM extends BaseAdapterORM
{
    public function someSpecificMethod()
    {

    }
}
```

Create extended ODM event adapter:

``` php
<?php
// file: vendor/Extension/Encoder/Mapping/Event/Adapter/ODM.php

namespace Extension\Encoder\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;

class ODM extends BaseAdapterODM
{
    public function someSpecificMethod()
    {

    }
}
```

It would be useful to make a common interface for those extended adapters.
Now every possible requirement is fulfilled and this may be useful.

Any suggestions on improvements are very welcome
