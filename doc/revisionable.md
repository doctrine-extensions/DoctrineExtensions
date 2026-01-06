# Revisionable Behavior Extension for Doctrine

The **Revisionable** behavior adds support for logging changes to and restoring prior versions of your Doctrine objects.

## Index

- [Differences Between Loggable and Revisionable](#differences-between-loggable-and-revisionable)
- [Getting Started](#getting-started)
- [Configuring Revisionable Objects](#configuring-revisionable-objects)
- [Customizing The Revision Model](#customizing-the-revision-model)
- [Object Repositories](#object-repositories)
    - [Fetching a Model's Revisions](#fetching-a-models-revisions)
    - [Revert a Model to a Previous Version](#revert-a-model-to-a-previous-version)

## Differences Between Loggable and Revisionable

The revisionable extension is a modern implementation of the loggable extension, and while largely similar, there are
some underlying differences in the out-of-the-box features in each extension.

### JSON Field Storage

When using the revisionable extension with the Doctrine DBAL and ORM, the default `Revision` entity stores its data in
a JSON column, whereas the loggable extension uses an array column (which under the hood is transformed to a serialized
array). The array column type was deprecated in DBAL 3.x and removed in 4.0 in favor of the JSON column type, which requires
a data migration since the two field types are not directly compatible.

For those using the Doctrine MongoDB ODM, there is no change in the underlying mapping configuration.

### Normalized Data Array

The loggable extension would store the changes as provided by the underlying model. This would result in PHP objects
(such as the core `DateTimeImmutable` class) being saved in the serialized payload. The revisionable extension saves
normalized values using the `Type::convertToDatabaseValue()` APIs from each supported object manager. As a side effect,
this also means that when reverting models to an older state, the `Type::convertToPHPValue()` APIs are used to restore
values.

As a practical example, this is the payload saved to a `LogEntry` when using the DBAL:

```shell
a:4:{s:5:"title";s:5:"Title";s:9:"publishAt";O:17:"DateTimeImmutable":3:{s:4:"date";s:26:"2024-06-24 23:00:00.000000";s:13:"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}s:11:"author.name";s:8:"John Doe";s:12:"author.email";s:12:"john@doe.com";}
```

And this is the equivalent value when saving a `Revision`:

```json
{
	"title": "Title",
	"publishAt": "2024-06-24 23:00:00",
	"author.name": "John Doe",
	"author.email": "john@doe.com"
}
```

## Getting Started

The revisionable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\Revisionable\RevisionableListener;

$listener = new RevisionableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

Then, once your application has it available (i.e. after validating the authentication for your user during an HTTP request),
you can set a reference to the user who performed actions on a revisionable model.

The user reference can be set through either an [actor provider service](./utils/actor-provider.md) or by calling the
listener's `setUsername` method with a resolved user.

> [!TIP]
> When an actor provider is given to the extension, any data set with the `setUsername` method will be ignored.

```php
// The $provider must be an implementation of Gedmo\Tool\ActorProviderInterface
$listener->setActorProvider($provider);

// The $user can be either an object or a string
$listener->setUsername($user);
```

## Configuring Revisionable Objects

The revisionable extension can be configured with [annotations](./annotations.md#revisionable-extension),
[attributes](./attributes.md#revisionable-extension), or XML configuration (matching the mapping of
your domain models). The full configuration for annotations and attributes can be reviewed in
the linked documentation.

The below examples show the simplest and default configuration for the extension, logging changes for defined fields.

### Attribute Configuration

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Revisionable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $published = false;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\KeepRevisions]
    public ?string $title = null;
}
```

### XML Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="App\Model\Article" table="articles">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="published" type="boolean"/>

        <field name="title" type="string">
            <gedmo:keep-revisions/>
        </field>

        <gedmo:revisionable/>
    </entity>
</doctrine-mapping>
```

### Annotation Configuration

> [!NOTE]
> Support for annotations is deprecated and will be removed in 4.0.

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Revisionable
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $published = false;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\KeepRevisions
     */
    public ?string $title = null;
}
```

## Customizing The Revision Model

When configuring revisionable models, you are able to specify a custom model to be used for the revisions for objects
of that type using the `revisionClass` parameter:

### Attribute Configuration

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Revisionable(revisionClass: ArticleRevision::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;
}
```

### XML Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="App\Model\Article" table="articles">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <gedmo:revisionable revision-class="App\Model\ArticleRevision"/>
    </entity>
</doctrine-mapping>
```

A custom model must implement `Gedmo\Revisionable\RevisionInterface`. For convenience, we recommend extending from
`Gedmo\Revisionable\Entity\MappedSuperClass\AbstractRevision` for Doctrine ORM users or
`Gedmo\Revisionable\Document\MappedSuperClass\AbstractRevision` for Doctrine MongoDB ODM users, which provides a default
mapping configuration for each object manager.

## Object Repositories

The revisionable extension includes a `Doctrine\Persistence\ObjectRepository` implementation for each supported object manager
that provides out-of-the-box features for all revision models. When creating custom models, you are welcome to extend
from either `Gedmo\Revisionable\Entity\Repository\RevisionRepository` for Doctrine ORM users or
`Gedmo\Revisionable\Document\Repository\RevisionRepository` for Doctrine MongoDB ODM users to provide these features.

### Fetching a Model's Revisions

The repository classes provide a `getRevisions` method which allows fetching the list of revisions for a given model.

```php
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Revisionable\Entity\Revision;
use Gedmo\Revisionable\Entity\Repository\RevisionRepository;
use Gedmo\Revisionable\RevisionableListener;

/** @var EntityManagerInterface $em */

// Load our revisionable model
$article = $em->find(Article::class, 1);

// Next, get the Revision repository
/** @var RevisionRepository $repo */
$repo = $em->getRepository(Revision::class);

// Lastly, get the article's revisions
$revisions = $repo->getRevisions($article);
```

### Revert a Model to a Previous Version

The repository classes provide a `revert` method which allows reverting a model to a previous version. The repository
will incrementally revert back to the version specified (for example, a model is currently on version 5, and you want to
revert to version 2, it will restore the state of version 4, then version 3, and finally, version 2).

```php
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Revisionable\Entity\Revision;
use Gedmo\Revisionable\Entity\Repository\RevisionRepository;
use Gedmo\Revisionable\RevisionableListener;

/** @var EntityManagerInterface $em */

// Load our revisionable model
$article = $em->find(Article::class, 1);

// Next, get the Revision repository
/** @var RevisionRepository $repo */
$repo = $em->getRepository(Revision::class);

// We are now able to revert to an older version
$repo->revert($article, 2);
```
