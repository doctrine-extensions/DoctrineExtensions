# Sluggable behavior extension for Doctrine 2

**Sluggable** behavior will build the slug of predefined fields on a given field
which should store the slug

Features:

- Automatic predifined field transformation into slug
- ORM and ODM support using same listener
- Slugs can be unique and styled
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions
- Multiple slugs, diferent slugs can link to same fields

[blog_reference]: http://gediminasm.org/article/sluggable-behavior-extension-for-doctrine-2 "Sluggable extension for Doctrine 2 makes automatic record field transformations into url friendly names"
[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2012-02-26**

- Remove slug handlers were removed because of complications it brought together


Update **2011-09-11**

- Refactored sluggable for doctrine2.2 by specifieng slug fields directly in slug annotation
- Slug handler functionality, possibility to create custom ones or use built-in
tree path handler or linked slug through single valued association
- Updated documentation mapping examples for 2.1.x version or higher

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager and any number of them

Update **2010-12-23**

- Full support for unique index on slug field,
no more exceptions during concurrent flushes.

**Note:**

- There is a reported [issue](https://github.com/l3pp4rd/DoctrineExtensions/issues/254) that sluggable transliterator
does not work on OSX 10.6 its ok starting again from 10.7 version. To overcome the problem
you can use your [custom transliterator](#transliterator)
- You can [test live][blog_test] on this blog
- Public [Sluggable repository](http://github.com/l3pp4rd/DoctrineExtensions "Sluggable extension on Github") is available on github
- Last update date: **2012-02-26**

**Portability:**

- **Sluggable** is now available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Sluggable**
behavior

Content:
    
- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)
- Custom [transliterator](#transliterator)
- Advanced usage [examples](#advanced-examples)
- Using [slug handlers](#slug-handlers)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

<a name="entity-mapping"></a>

## Sluggable Entity example:

### Sluggable annotations:

- **@Gedmo\Mapping\Annotation\Slug** it will use this column to store **slug** generated
**fields** option must be specified, an array of field names to slug

**Note:** that Sluggable interface is not necessary, except in cases there
you need to identify entity as being Sluggable. The metadata is loaded only once then
cache is activated

**Note:** 2.0.x version of extensions used @Gedmo\Mapping\Annotation\Sluggable to identify
the field for slug

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity
 */
class Article
{
    /** 
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @ORM\Column(length=16)
     */
    private $code;

    /**
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

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

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
```

<a name="document-mapping"></a>

## Sluggable Document example:

``` php
<?php
namespace Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** 
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @ODM\String
     */
    private $code;

    /**
     * @Gedmo\Slug(fields={"title", "code"})
     * @ODM\String
     */
    private $slug;

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

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
```

<a name="yaml-mapping"></a>

## Yaml mapping example

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
    code:
      type: string
      length: 16
    slug:
      type: string
      length: 128
      gedmo:
        slug:
          separator: _
          style: camel
          fields:
            - title
            - code
  indexes:
    search_idx:
      columns: slug
```

<a name="xml-mapping"></a>

## Xml mapping example

**Note:** xml driver is not yet adapted for single slug mapping

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
    <entity name="Mapping\Fixture\Xml\Sluggable" table="sluggables">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string" length="128"/>
        <field name="code" type="string" length="16"/>
        <field name="ean" type="string" length="13"/>
        <field name="slug" type="string" length="156" unique="true">
            <gedmo:slug unique="true" style="camel" updatable="false" separator="_", fields="title,code,ean" />
        </field>
    </entity>
</doctrine-mapping>
```

<a name="basic-examples"></a>

## Basic usage examples:

### To save **Article** and generate slug simply use:

``` php
<?php
$article = new Article();
$article->setTitle('the title');
$article->setCode('my code');
$this->em->persist($article);
$this->em->flush();

echo $article->getSlug();
// prints: the-title-my-code
```

### Some other configuration options for **slug** annotation:

- **fields** (required, default=[]) - list of fields for slug
- **updatable** (optional, default=true) - **true** to update the slug on sluggable field changes, **false** - otherwise
- **unique** (optional, default=true) - **true** if slug should be unique and if identical it will be prefixed, **false** - otherwise
- **separator** (optional, default="-") - separator which will separate words in slug
- **style** (optional, default="default") - **"default"** all letters will be lowercase, **"camel"** - first word letter will be uppercase

### Example

``` php
<?php
class Article
{
    // ...
    /**
     * @Gedmo\Slug(fields={"title"}, style="camel", separator="_", updatable=false, unique=false)
     * @Doctrine\ORM\Mapping\Column(length=128, unique=true)
     */
    private $slug;
    // ...

    // ...
    /**
     * @Doctrine\ORM\Mapping\Column(length=128)
     */
    private $title;
    // ...
}
```

And now test the result:

``` php
<?php
$article = new Article();
$article->setTitle('the title');
$article->setCode('my code');
$this->em->persist($article);
$this->em->flush();

echo $article->getSlug();
// prints: The_Title_My_Code
```

<a name="transliterator"></a>

## Custom transliterator

To set your own custom transliterator, which would be used to generate the slug, use:

``` php
<?php

$callable = array('My\Class', 'transliterationMethod');
$sluggableListener->setTransliterator($callable);

// or use a closure

$callable = function($text, $separatorUsed, $objectBeingSlugged) {
    // ...
    return $transliteratedText;
};
$sluggableListener->setTransliterator($callable);
```

<a name="advanced-examples"></a>

## Advanced examples:

### Using TranslationListener to translate our slug

If you want to attach **TranslationListener** also add it to EventManager after
the **SluggableListener**. It is important because slug must be generated first
before the creation of it`s translation.

``` php
<?php
$evm = new \Doctrine\Common\EventManager();
$sluggableListener = new \Gedmo\Sluggable\SluggableListener();
$evm->addEventSubscriber($sluggableListener);
$translatableListener = new \Gedmo\Translatable\TranslationListener();
$translatableListener->setTranslatableLocale('en_us');
$evm->addEventSubscriber($translatableListener);
// now this event manager should be passed to entity manager constructor
```

And the Entity should look like:

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity
 */
class Article
{
    /** 
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=16)
     */
    private $code;
    
    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    private $uniqueTitle;
    
    /**
     * @Gedmo\Slug(fields={"uniqueTitle"})
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $uniqueSlug;

    

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

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }
    
    public function getUniqueSlug()
    {
        return $this->uniqueSlug;
    }
}
```

Now the generated slug will be translated by Translatable behavior

Any suggestions on improvements are very welcome
