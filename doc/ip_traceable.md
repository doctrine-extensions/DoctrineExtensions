# IpTraceable behavior extension for Doctrine 2

**IpTraceable** behavior will automate the update of IP trace
on your Entities or Documents. It works through annotations and can update
fields on creation, update, property subset update, or even on specific property value change.

This is very similar to Timestampable but sets a string.

Note that you need to set the IP on the IpTraceableListener (unless you use the
Symfony2 extension which does automatically assign the current request IP).


Features:

- Automatic predefined ip field update on creation, update, property subset update, and even on record property changes
- ORM and ODM support using same listener
- Specific annotations for properties, and no interface required
- Can react to specific property or relation changes to specific value
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions


**Symfony:**

- **IpTraceable** is not yet available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
for **Symfony2**, together with all other extensions

This article will cover the basic installation and functionality of **IpTraceable** behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Advanced usage [examples](#advanced-examples)
- Using [Traits](#traits)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

<a name="entity-mapping"></a>

## IpTraceable Entity example:

### IpTraceable annotations:
- **@Gedmo\Mapping\Annotation\IpTraceable** this annotation tells that this column is ipTraceable
by default it updates this column on update. If column is not a string field it will trigger an exception.

Available configuration options:

- **on** - is main option and can be **create, update, change** this tells when it
should be updated
- **field** - only valid if **on="change"** is specified, tracks property or a list of properties for changes
- **value** - only valid if **on="change"** is specified and the tracked field is a single field (not an array), if the tracked field has this **value**
then it updates the trace

**Note:** that IpTraceable interface is not necessary, except in cases there
you need to identify entity as being IpTraceable. The metadata is loaded only once then
cache is activated

Column is a string field:

``` php
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
     * @ORM\Column(name="body", type="string")
     */
    private $body;

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
     * @var datetime $contentChangedFromIp
     *
     * @ORM\Column(name="content_changed_by", type="string", nullable=true, length=45)
     * @Gedmo\IpTraceable(on="change", field={"title", "body"})
     */
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

``` php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @var string $createdFromIp
     *
     * @ODM\String
     * @Gedmo\IpTraceable(on="create")
     */
    private $createdFromIp;

    /**
     * @var string $updatedFromIp
     *
     * @ODM\String
     * @Gedmo\IpTraceable
     */
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

<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
---
Entity\Article:
  type: entity
  table: articles
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    title:
      type: string
      length: 64
    createdFromIp:
      type: string
      length: 45
      nullable: true
      gedmo:
        ipTraceable:
          on: create
    updatedFromIp:
      type: string
      length: 45
      nullable: true
      gedmo:
        ipTraceable:
          on: update
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\IpTraceable" table="ip-traceable">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="createdFromIp" type="string", length="45", nullable="true">
            <gedmo:ip-traceable on="create"/>
        </field>
        <field name="updatedFromIp" type="string", length="45", nullable="true">
            <gedmo:ip-traceable on="update"/>
        </field>
        <field name="publishedFromIp" type="string" nullable="true", length="45">
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

``` php
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

``` php
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

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
---
Entity\Article:
  type: entity
  table: articles
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    title:
      type: string
      length: 64
    createdFromIp:
      type: string
      length: 45
      nullable: true
      gedmo:
        ipTraceable:
          on: create
    updatedFromIp:
      type: string
      length: 45
      nullable: true
      gedmo:
        ipTraceable:
          on: update
    publishedFromIp:
      type: string
      length: 45
      nullable: true
      gedmo:
        ipTraceable:
          on: change
          field: type.title
          value: Published
  manyToOne:
    type:
      targetEntity: Entity\Type
      inversedBy: articles
```

Now few operations to get it all done:

``` php
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

You can use IpTraceable traits for quick **createdFromIp** **updatedFromIp** string definitions
when using annotation mapping.

**Note:** this feature is only available since php **5.4.0**. And you are not required
to use the Traits provided by extensions.

``` php
<?php
namespace IpTraceable\Fixture;

use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
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

**Note:** you must import **Gedmo\Mapping\Annotation as Gedmo** and **Doctrine\ORM\Mapping as ORM**
annotations. If you use mongodb ODM import **Doctrine\ODM\MongoDB\Mapping\Annotations as ODM** and
**IpTraceableDocument** instead.

The Traits are very simplistic - if you use different field names it is recommended to simply create your
own Traits specific to your project. The ones provided by this bundle can be used as example.


## Example of implementation in Symfony2

In your Sf2 application, declare an event subscriber that automatically set IP value on IpTraceableListener.

### Code of subscriber class

``` php
<?php

namespace Acme\DemoBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

use Gedmo\IpTraceable\IpTraceableListener;

/**
 * IpTraceSubscriber
 */
class IpTraceSubscriber implements EventSubscriberInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var IpTraceableListener
     */
    private $ipTraceableListener;

    public function __construct(IpTraceableListener $ipTraceableListener, Request $request = null)
    {
        $this->ipTraceableListener = $ipTraceableListener;
        $this->request = $request;
    }

    /**
     * Set the username from the security context by listening on core.request
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null === $this->request) {
            return;
        }

        // If you use a cache like Varnish, you may want to set a proxy to Request::getClientIp() method 
        // $this->request->setTrustedProxies(array('127.0.0.1'));

        // $ip = $_SERVER['REMOTE_ADDR'];
        $ip = $this->request->getClientIp();
        
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

``` xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="alterphp_doctrine_extensions.event_listener.ip_trace.class">Acme\DemoBundle\EventListener\IpTraceListener</parameter>
    </parameters>

    <services>
        
        ...

        <service id="gedmo_doctrine_extensions.listener.ip_traceable" class="Gedmo\IpTraceable\IpTraceableListener" public="false">
            <tag name="doctrine.event_subscriber" connection="default" />
            <call method="setAnnotationReader">
                <argument type="service" id="annotation_reader" />
            </call>
        </service>

        <service id="alterphp_doctrine_extensions.event_listener.ip_trace" class="%alterphp_doctrine_extensions.event_listener.ip_trace.class%" public="false" scope="request">
            <argument type="service" id="gedmo_doctrine_extensions.listener.ip_traceable" />
            <argument type="service" id="request" on-invalid="null" />
            <tag name="kernel.event_subscriber" />
        </service>

    </services>
</container>

```
