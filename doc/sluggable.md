# Sluggable behavior extension for Doctrine

**Sluggable** behavior will build the slug of predefined fields on a given field
which should store the slug

Features:

- Automatic predefined field transformation into slug
- ORM and ODM support using same listener
- Slugs can be unique and styled, even with prefixes and/or suffixes
- Can be nested with other behaviors
- Attribute, Annotation and Xml mapping support for extensions
- Multiple slugs, different slugs can link to same fields

**Note:**

- There is a reported [issue](https://github.com/doctrine-extensions/DoctrineExtensions/issues/254) that sluggable transliterator
does not work on OSX 10.6 its ok starting again from 10.7 version. To overcome the problem
you can use your [custom transliterator](#transliterator)
- Public [Sluggable repository](https://github.com/doctrine-extensions/DoctrineExtensions "Sluggable extension on Github") is available on github
- For usage together with **SoftDeleteable** in order to take into account softdeleted entities while generating unique
slug, you must explicitly call **addManagedFilter** with a name of softdeleteable filter, so it can be disabled during
slug updates. The best place to do it, is when initializing sluggable listener. That will be automated in the future.

This article will cover the basic installation and functionality of **Sluggable**
behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)
- Custom [transliterator](#transliterator)
- Advanced usage [examples](#advanced-examples)
- Using [slug handlers](#slug-handlers)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in most optimized way.

<a name="entity-mapping"></a>

## Sluggable mapping:

### Sluggable annotations:

- **@Gedmo\Mapping\Annotation\Slug** it will to store in this property the **slug** generated.
**fields** option must be specified as an array of field names that will be used for generate the slug.

### Sluggable attributes:

- **\#[Gedmo\Mapping\Annotation\Slug]** it will to store in this property the **slug** generated.
**fields** option must be specified as an array of field names that will be used for generate the slug.

**Note:** the examples shown here are using annotations and attributes for mapping, you should use
one of them, not both.

**Note:** that Sluggable interface is not necessary, except in cases there
you need to identify entity as being Sluggable. The metadata is loaded only once then
cache is activated

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity
 */
#[ORM\Table(name: 'articles')]
#[ORM\Entity]
class Article
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTIFY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(length=16)
     */
    #[ORM\Column(length: 16)]
    private $code;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(length=128, unique=true)
     */
    #[ORM\Column(length: 128, unique: true)]
    #[Gedmo\Slug(fields: ['title', 'code'])]
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

```php
<?php
namespace Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
#[ODM\Document(collection: 'articles')]
class Article
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $title;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $code;

    /**
     * @Gedmo\Slug(fields={"title", "code"})
     * @ODM\Field(type="string")
     */
    #[Gedmo\Slug(fields: ['title', 'code'])]
    #[ODM\Field(type: Type::STRING)]
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

<a name="xml-mapping"></a>

## Xml mapping example

**Note:** xml driver is not yet adapted for single slug mapping

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
    <entity name="Entity\Article" table="sluggables">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string" length="128"/>
        <field name="code" type="string" length="16"/>
        <field name="ean" type="string" length="13"/>
        <field name="slug" type="string" length="156" unique="true">
            <gedmo:slug unique="true" style="camel" updatable="false" separator="_" fields="title,code,ean" />
        </field>
    </entity>
</doctrine-mapping>
```

<a name="basic-examples"></a>

## Basic usage examples:

### To save **Article** and generate slug simply use:

```php
<?php
$article = new Article();
$article->setTitle('the title');
$article->setCode('my code');
$this->em->persist($article);
$this->em->flush();

echo $article->getSlug();
// prints: the-title-my-code
```

### Some other configuration options for **slug** annotation and attribute:

- **fields** (required, default=[]) - list of fields for slug
- **updatable** (optional, default=true) - **true** to update the slug on sluggable field changes, **false** - otherwise
- **unique** (optional, default=true) - **true** if slug should be unique and if identical it will be suffixed, **false** - otherwise
- **unique_base** (optional, default=null) - used in conjunction with **unique**. The name of the entity property that should be used as a key when doing a uniqueness check.
- **separator** (optional, default="-") - separator which will separate words in slug
- **prefix** (optional, default="") - prefix which will be added to the generated slug
- **suffix** (optional, default="") - suffix which will be added to the generated slug
- **style** (optional, default="default") - **"default"** all letters will be lowercase, **"camel"** - first word letter will be uppercase, **"upper"**- all word letter will be uppercase and **"lower"**- all word letter will be lowercase
- **handlers** (only available in annotations, optional, default=[]) - list of slug handlers, like tree path slug, or customized, for example see bellow

**Note**: handlers are totally optional

When using attributes, SlugHandlers are defined directly at property level and their options are passed as an array
instead of `SlugHandlerOption`.

**TreeSlugHandler**

```php
<?php

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\TreeSlugHandler;

/**
 * @Gedmo\Slug(handlers={
 *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
 *          @Gedmo\SlugHandlerOption(name="parentRelationField", value="parent"),
 *          @Gedmo\SlugHandlerOption(name="separator", value="/")
 *      })
 * }, fields={"title", "code"})
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
#[Gedmo\Slug(fields: ['title', 'code'])]
#[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: [
    'parentRelationField' => 'parent',
    'separator' => '/',
])]
#[Doctrine\ORM\Mapping\Column(length: 64, unique: true)]
private $slug;
```

**RelativeSlugHandler**:

```php
<?php

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * Person domain object class
 *
 * @Gedmo\Mapping\Annotation\Slug(handlers={
 *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationField", value="category"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationSlugField", value="slug"),
 *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="separator", value="/")
 *      })
 * }, fields={"title", "code"})
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
#[Gedmo\Slug(fields: ['title', 'code'])]
#[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: [
    'relationField' => 'category',
    'relationSlugField' => 'slug',
    'separator' => '/',
])]
#[Doctrine\ORM\Mapping\Column(length: 64, unique: true)]`
private $slug;
```

If the relationSlugField you are using is not a slug field but a string field for example you can make
sure the relationSlugField is also urilized with:

```php
<?php

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * Person domain object class
 *
 * @Gedmo\Slug(handlers={
 *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
 *          @Gedmo\SlugHandlerOption(name="relationField", value="category"),
 *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="title"),
 *          @Gedmo\SlugHandlerOption(name="separator", value="/"),
 *          @Gedmo\SlugHandlerOption(name="urilize", value=true)
 *      })
 * }, fields={"title", "code"})
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
#[Gedmo\Slug(fields: ['title', 'code'])]
#[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: [
    'relationField' => 'category',
    'relationSlugField' => 'title',
    'separator' => '/',
    'urilize' => true,
])]
#[Doctrine\ORM\Mapping\Column(length: 64, unique: true)]
private $slug;
```

This will make sure that the 'title' field in the category entity is url friendly.

**Note:** if you used **RelativeSlugHandler** - relation object should use in order to sync changes:

**InversedRelativeSlugHandler**

```php
<?php

use App\Entity\Person;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\InversedRelativeSlugHandler;

/**
 * Category domain object class
 *
 * @Gedmo\Slug(handlers={
 *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
 *          @Gedmo\SlugHandlerOption(name="relationClass", value="App\Entity\Person"),
 *          @Gedmo\SlugHandlerOption(name="mappedBy", value="category"),
 *          @Gedmo\SlugHandlerOption(name="inverseSlugField", value="slug")
 *      })
 * }, fields={"title"})
 * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
 */
#[Gedmo\Slug(fields: ['title'])]
#[Gedmo\SlugHandler(class: InversedRelativeSlugHandler::class, options: [
    'relationClass' => Person::class,
    'mappedBy' => 'category',
    'inverseSlugField' => 'slug',
])]
#[Doctrine\ORM\Mapping\Column(length: 64, unique: true)]
private $slug;
```

### Example

```php
<?php

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;

class Article
{
    // ...
    /**
     * @Gedmo\Slug(fields={"title", "created"}, style="camel", separator="_", updatable=false, unique=false, dateFormat="d/m/Y H-i-s")
     * @Doctrine\ORM\Mapping\Column(length=128, unique=true)
     */
    #[Doctrine\ORM\Mapping\Column(length: 128, unique: true)]
    #[Gedmo\Slug(
        fields: ['title', 'created'],
        style: 'camel',
        separator: '_',
        updatable: false,
        unique: false,
        dateFormat: 'd/m/Y H-i-s',
    )]
    private $slug;
    // ...

    // ...
    /**
     * @Doctrine\ORM\Mapping\Column(type="datetime", name="created_at")
     */
    #[Doctrine\ORM\Mapping\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private $createdAt;

    // ...
    /**
     * @Doctrine\ORM\Mapping\Column(length=128)
     */
    #[Doctrine\ORM\Mapping\Column(length: 128)]
    private $title;
    // ...
    public function __construct()
    {
      $this->createdAt = new \DateTime;
    }
}
```

And now test the result:

```php
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

```php
<?php

use My\MyClass;

$callable = [MyClass::class, 'transliterationMethod'];
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

### Regenerating slug

In case if you want the slug to regenerate itself based on sluggable fields, set the slug to **null**.

*Note: in previous versions empty strings would also cause the slug to be regenerated. This behaviour was changed in v2.3.8.*

```php
<?php
$entity = $em->find('Entity\Something', $id);
$entity->setSlug(null);

$em->persist($entity);
$em->flush();
```

### Setting the slug manually

Sometimes you might need to set it manually, etc if generated one does not look satisfying enough.
Sluggable will ensure uniqueness of the slug.

```php
<?php
$entity = new SomeEntity;
$entity->setSluggableField('won\'t be taken into account');
$entity->setSlug('the required slug, set manually');

$em->persist($entity);
$em->flush();

echo $entity->getSlug(); // outputs: "the-required-slug-set-manually"
```

### Using TranslatableListener to translate our slug

If you want to attach **TranslatableListener** also add it to EventManager after
the **SluggableListener**. It is important because slug must be generated first
before the creation of it`s translation.

```php
<?php
$evm = new \Doctrine\Common\EventManager();
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
     * @Gedmo\Slug(fields={"uniqueTitle"}, prefix="some-prefix-")
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

<a name="slug-handlers"></a>

## Using slug handlers:

There are built-in slug handlers like described in configuration options of slug, but there
can be also customized slug handlers depending on use cases. Usually the most logic use case
is for related slug. For instance if user has a **ManyToOne relation to a **Company** we
would like to have a url like `http://example.com/knplabs/gedi` where **KnpLabs**
is a company and user name is **Gedi**. In this case relation has a path separator **/**

User entity example:

```php
<?php

namespace Sluggable\Fixture\Handler;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
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
    private $username;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationField", value="company"),
     *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="alias"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, separator="-", updatable=true, fields={"username"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity="Company")
     */
    private $company;

    public function setCompany(Company $company = null)
    {
        $this->company = $company;
    }

    public function getCompany()
    {
        return $this->company;
    }

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

    public function getSlug()
    {
        return $this->slug;
    }
}
```

Company entity example:

```php
<?php

namespace Sluggable\Fixture\Handler;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Company
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
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationClass", value="Sluggable\Fixture\Handler\User"),
     *          @Gedmo\SlugHandlerOption(name="mappedBy", value="company"),
     *          @Gedmo\SlugHandlerOption(name="inverseSlugField", value="slug")
     *      })
     * }, fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    private $alias;

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

    public function getAlias()
    {
        return $this->alias;
    }
}
```

For other mapping drivers see
[xml](../tests/Gedmo/Mapping/Driver/Xml/Gedmo.Tests.Mapping.Fixture.Xml.Sluggable.dcm.xml) examples from tests

And the example usage:

```php
<?php
$company = new Company;
$company->setTitle('KnpLabs');
$em->persist($company);

$gedi = new User;
$gedi->setUsername('Gedi');
$gedi->setCompany($company);
$em->persist($gedi);

$em->flush();

echo $gedi->getSlug(); // outputs "knplabs/gedi"

$company->setTitle('KnpLabs Nantes');
$em->persist($company);
$em->flush();

echo $gedi->getSlug(); // outputs "knplabs-nantes/gedi"
```

**Note:** tree slug handler, takes a parent relation to build slug recursively.

Any suggestions on improvements are very welcome
