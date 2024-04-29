# IpTraceable behavior extension for Doctrine

**IpTraceable** behavior will automate the update of IP trace
on your Entities or Documents. It works through annotations or attributes and can update
fields on creation, update, property subset update, or even on specific property value change.

This is very similar to the Timestampable behavior.

Note that you need to set the IP on the IpTraceableListener (unless you use the
Symfony bundle which automatically assigns the current request IP).

Features:

- Automatic predefined ip field update on creation, update, property subset update, and even on record property changes
- ORM and ODM support using same listener
- Specific attributes and annotations for properties, and no interface required
- Can react to specific property or relation changes to specific value
- Can be nested with other behaviors
- Attribute, Annotation and XML mapping support for extensions

This article will cover the basic installation and functionality of the **IpTraceable** behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [XML](#xml-mapping) mapping example
- Advanced usage [examples](#advanced-examples)
- Using [Traits](#traits)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to set up and use the extensions.

<a name="entity-mapping"></a>

## IpTraceable Entity example:

### IpTraceable annotations:
- **@Gedmo\Mapping\Annotation\IpTraceable** this annotation tells that this column is ipTraceable
by default it updates this column on update. If column is not a string field it will trigger an exception.

### IpTraceable attributes:
- **\#[Gedmo\Mapping\Annotation\IpTraceable]** this attribute tells that this column is ipTraceable
  by default it updates this column on update. If column is not a string field it will trigger an exception.

Available configuration options:

- **on** - is main option and can be **create, update, change** this tells when it
should be updated
- **field** - only valid if **on="change"** is specified, tracks property or a list of properties for changes
- **value** - only valid if **on="change"** is specified and the tracked field is a single field (not an array), if the tracked field has this **value**
then it updates the trace

**Note:** the IpTraceable interface is not necessary, except in cases where
you need to identify the object as being IpTraceable in your application.
The metadata is loaded only once when the cache is activated.

**Note:** these examples are using annotations and attributes for mapping, you should only use
one of them, not both.

Column is a string field:

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article
{
    /**
     * @var int|null
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=128)
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var string|null
     * @ORM\Column(name="body", type="string")
     */
    #[ORM\Column(name: 'body', type: Types::STRING)]
    private $body;

    /**
     * @var string|null
     *
     * @Gedmo\IpTraceable(on="create")
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'create')]
    private $createdFromIp;

    /**
     * @var string|null
     *
     * @Gedmo\IpTraceable(on="update")
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'update')]
    private $updatedFromIp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content_changed_by", type="string", nullable=true, length=45)
     * @Gedmo\IpTraceable(on="change", field={"title", "body"})
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: ['title', 'body'])]
    private $contentChangedFromIp;

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

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }

    public function getUpdatedFromIp()
    {
        return $this->updatedFromIp;
    }

    public function getContentChangedFromIp()
    {
        return $this->contentChangedFromIp;
    }
}
```


<a name="document-mapping"></a>

## IpTraceable Document example:

```php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @ODM\Document(collection="articles")
 */
#[ODM\Document(collection: 'articles')]
class Article
{
    /** @ODM\Id */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $title;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable(on="create")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\IpTraceable(on: 'create')]
    private $createdFromIp;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\IpTraceable]
    private $updatedFromIp;

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

    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }

    public function getUpdatedFromIp()
    {
        return $this->updatedFromIp;
    }
}
```

Now on update and creation these annotated fields will be automatically updated

<a name="xml-mapping"></a>

## XML mapping example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\IpTraceable" table="ip-traceable">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="createdFromIp" type="string" length="45" nullable="true">
            <gedmo:ip-traceable on="create"/>
        </field>
        <field name="updatedFromIp" type="string" length="45" nullable="true">
            <gedmo:ip-traceable on="update"/>
        </field>
        <field name="publishedFromIp" type="string" nullable="true" length="45">
            <gedmo:ip-traceable on="change" field="status.title" value="Published"/>
        </field>

        <many-to-one field="status" target-entity="Status">
            <join-column name="status_id" referenced-column-name="id"/>
        </many-to-one>
    </entity>

</doctrine-mapping>
```

<a name="advanced-examples"></a>

## Advanced examples:

### Using dependency of property changes

Add another entity which would represent Article Type:

```php
<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Type
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="type")
     */
    private $articles;

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
}
```

Now update the Article Entity to reflect publishedFromIp on Type change:

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @var string $createdFromIp
     *
     * @Gedmo\IpTraceable(on="create")
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $createdFromIp;

    /**
     * @var string $updatedFromIp
     *
     * @Gedmo\IpTraceable(on="update")
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $updatedFromIp;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedFromIp="articles")
     */
    private $type;

    /**
     * @var string $publishedFromIp
     *
     * @ORM\Column(type="string", nullable=true, length=45)
     * @Gedmo\IpTraceable(on="change", field="type.title", value="Published")
     */
    private $publishedFromIp;

    public function setType($type)
    {
        $this->type = $type;
    }

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

    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }

    public function getUpdatedFromIp()
    {
        return $this->updatedFromIp;
    }

    public function getPublishedFromIp()
    {
        return $this->publishedFromIp;
    }
}
```

Now a few operations to get it all done:

```php
<?php
$article = new Article;
$article->setTitle('My Article');

$em->persist($article);
$em->flush();
// article: $createdFromIp, $updatedFromIp were set

$type = new Type;
$type->setTitle('Published');

$article = $em->getRepository('Entity\Article')->findByTitle('My Article');
$article->setType($type);

$em->persist($article);
$em->persist($type);
$em->flush();
// article: $publishedFromIp, $updatedFromIp were set

$article->getPublishedFromIp(); // the IP that published this article
```

Easy like that, any suggestions on improvements are very welcome


<a name="traits"></a>

## Traits

You can use the IpTraceable traits to quickly add **createdFromIp** and **updatedFromIp** fields to your objects
when using annotation or attribute mapping.

There is also a trait without annotation or attribute mappings for easy integration.

**Note:** You are not required to use the traits provided by extensions.

```php
<?php
namespace IpTraceable\Fixture;

use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UsingTrait
{
    /**
     * Hook ip-traceable behavior
     * updates createdFromIp, updatedFromIp fields
     */
    use IpTraceableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $title;
}
```

The Traits are very simplistic - if you use different field names it is recommended to simply create your
own Traits specific to your project. The ones provided by this bundle can be used as example.


## Example of implementation in Symfony

In your Symfony application, declare an event subscriber that automatically sets the IP address value on IpTraceableListener.

### Example event subscriber class

```php
<?php

namespace Acme\DemoBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

use Gedmo\IpTraceable\IpTraceableListener;

/**
 * IpTraceSubscriber
 */
class IpTraceSubscriber implements EventSubscriberInterface
{
    /**
     * @var IpTraceableListener
     */
    private $ipTraceableListener;

    public function __construct(IpTraceableListener $ipTraceableListener)
    {
        $this->ipTraceableListener = $ipTraceableListener;
    }

    /**
     * Set the IP address during the `kernel.request` event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // Generally, the listener should only be updated during the main request
        if (!$event->isMainRequest()) {
            return;
        }

        // If you use a cache like Varnish, you may want to set a proxy to Request::getClientIp() method
        // $event->getRequest()->setTrustedProxies(array('127.0.0.1'));

        $ip = $event->getRequest()->getClientIp();

        if (null !== $ip) {
            $this->ipTraceableListener->setIpValue($ip);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }
}

```

### Configuration for services.xml

```xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="acme_doctrine_extensions.event_listener.ip_trace.class">Acme\DemoBundle\EventListener\IpTraceListener</parameter>
    </parameters>

    <services>

        <!-- If your application is using PHP 8 and attributes, you can provide this attribute reader to the listener instead of an annotation reader -->
        <service id="gedmo_doctrine_extensions.mapping.driver.attribute" class="Gedmo\Mapping\Driver\AttributeReader" public="false" />

        <service id="gedmo_doctrine_extensions.listener.ip_traceable" class="Gedmo\IpTraceable\IpTraceableListener" public="false">
            <tag name="doctrine.event_subscriber" connection="default" />
            <call method="setAnnotationReader">
                <!-- Uncomment the below argument if using attributes, and comment the argument for the annotation reader -->
                <!-- <argument type="service" id="gedmo_doctrine_extensions.mapping.driver.attribute" /> -->
                <!-- The `annotation_reader` service was deprecated in Symfony 6.4 and removed in Symfony 7.0 -->
                <argument type="service" id="annotation_reader" />
            </call>
        </service>

        <service id="acme_doctrine_extensions.event_listener.ip_trace" class="%acme_doctrine_extensions.event_listener.ip_trace.class%" public="false">
            <argument type="service" id="gedmo_doctrine_extensions.listener.ip_traceable" />
            <tag name="kernel.event_subscriber" />
        </service>

    </services>
</container>

```
