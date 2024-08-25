# Annotations Reference

> [!IMPORTANT]
> Support for annotations is deprecated and will be removed in 4.0. PHP 8 users are encouraged to migrate and use [attributes](./attributes.md) instead of annotations. To use annotations, you will need the [`doctrine/annotations`](https://www.doctrine-project.org/projects/annotations.html) library.

Below you will a reference for annotations supported in this extensions library.
There will be introduction on usage with examples. For more detailed usage of each
extension, refer to the extension's documentation page.

## Index

- [Blameable Extension](#blameable-extension)
- [IP Traceable Extension](#ip-traceable-extension)
- [Loggable Extension](#loggable-extension)
- [Reference Integrity Extension](#reference-integrity-extension)
- [References Extension](#references-extension)
- [Sluggable Extension](#sluggable-extension)
- [Soft Deleteable Extension](#soft-deleteable-extension)
- [Sortable Extension](#sortable-extension)
- [Timestampable Extension](#timestampable-extension)
- [Translatable Extension](#translatable-extension)
- [Tree Extension](#tree-extension)
- [Uploadable Extension](#uploadable-extension)

## Reference

### Blameable Extension

The below annotations are used to configure the [Blameable extension](./blameable.md).

#### `@Gedmo\Mapping\Annotation\Blameable`

The `Blameable` annotation is a property annotation used to identify fields which are updated to show information
about the last user to update the mapped object. A blameable field may have either a string value or a one-to-many
relationship with another entity.

Required Attributes:

- **on** - By default, the annotation configures the property to be updated when an object is updated;
           this can be set to one of \[`change`, `create`, `update`\]

Optional Attributes:

- **field** - An optional list of properties to limit updates to the blameable field; this option is only
              used when the **on** option is set to "change" and can be a dot separated path to indicate
              properties on a related object are watched (i.e. `user.email` to reference the `$email` property
              of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the blameable field;
              this option is only used when the **on** option is set to "change"

> [!WARNING]
> When both the **field** and **value** options are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=128)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $body = null;

    /**
     * Blameable field storing a username for the user who created the entity
     *
     * @ORM\Column(type="string")
     * @Gedmo\Blameable(on="create")
     */
    public ?string $createdBy = null;

    /**
     * Blameable field storing a User relation for the user who updated the entity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @Gedmo\Blameable(on="update")
     */
    public ?User $updatedBy = null;

    /**
     * Blameable field storing a username for the user who last changed either the title or body fields
     *
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field={"title", "body"})
     */
    public ?string $contentChangedBy = null;
}
```

### IP Traceable Extension

The below annotations are used to configure the [IP Traceable extension](./ip_traceable.md).

#### `@Gedmo\Mapping\Annotation\IpTraceable`

The `IpTraceable` annotation is a property annotation used to identify fields which are updated to record the
IP address from the last user to update the mapped object. A traceable field must be a string.

Required Attributes:

- **on** - By default, the annotation configures the property to be updated when an object is updated;
           this can be set to one of \[`change`, `create`, `update`\]

Optional Attributes:

- **field** - An optional list of properties to limit updates to the IP traceable field; this option is only
              used when the **on** option is set to "change" and can be a dot separated path to indicate
              properties on a related object are watched (i.e. `user.email` to reference the `$email` property
              of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the IP traceable field;
              this option is only used when the **on** option is set to "change"

> [!WARNING]
> When both the **field** and **value** options are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=128)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $body = null;

    /**
     * Traceable field storing an IP address for the user who created the entity
     *
     * @ORM\Column(type="string")
     * @Gedmo\IpTraceable(on="create")
     */
    public ?string $createdByIp = null;

    /**
     * Traceable field storing an IP address for the user who updated the entity
     *
     * @ORM\Column(type="string")
     * @Gedmo\IpTraceable(on="update")
     */
    public ?string $updatedByIp = null;

    /**
     * Traceable field storing an IP address for the user who last changed either the title or body fields
     *
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\IpTraceable(on="change", field={"title", "body"})
     */
    public ?string $contentChangedByIp = null;
}
```

### Loggable Extension

The below annotations are used to configure the [Loggable extension](./loggable.md).

#### `@Gedmo\Mapping\Annotation\Loggable`

The `Loggable` annotation is a class annotation used to identify objects which can have changes logged,
all loggable objects **MUST** have this annotation.

Required Attributes:

- **logEntryClass** - A custom model class implementing `Gedmo\Loggable\LogEntryInterface` to use for logging changes;
                      defaults to `Gedmo\Loggable\Entity\LogEntry` for Doctrine ORM users or
                      `Gedmo\Loggable\Document\LogEntry` for Doctrine MongoDB ODM users

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Loggable(logEntryClass="App\Entity\ArticleLogEntry")
 */
class Article {}
```

#### `@Gedmo\Mapping\Annotation\Versioned`

The `Versioned` annotation is a property annotation used to identify properties whose changes should be logged.
This annotation can be set for properties with a single value (i.e. a scalar type or an object such as
`DateTimeInterface`), but not for collections. Versioned fields can be restored to an earlier version.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class Comment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Article", inversedBy="comments")
     * @Gedmo\Versioned
     */
    public ?Article $article = null;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\Versioned
     */
    public ?string $body = null;
}
```

### Reference Integrity Extension

The below annotations are used to configure the [Reference Integrity extension](./reference_integrity.md).

> [!WARNING]
> This extension is only usable with the Doctrine MongoDB ODM

#### `@Gedmo\Mapping\Annotation\ReferenceIntegrity`

The `ReferenceIntegrity` annotation is a property annotation used to identify fields where referential integrity
should be checked. The annotation must be used on a property which references another document, and the reference
configuration must have a `mappedBy` configuration.

Required Attributes:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

Example:

```php
<?php
namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public ?string $id = null;

    /**
     * @ODM\Field(type="string")
     */
    public ?string $title = null;

    /**
     * @ODM\ReferenceOne(targetDocument="App\Document\User", mappedBy="articles")
     * @Gedmo\ReferenceIntegrity("nullify")
     */
    public ?User $author = null;

    /**
     * @var Collection<int, Comment>
     *
     * @ODM\ReferenceMany(targetDocument="App\Document\Comment", mappedBy="article")
     * @Gedmo\ReferenceIntegrity("nullify")
     */
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

### References Extension

The below annotations are used to configure the [References extension](./references.md).

#### `@Gedmo\Mapping\Annotation\ReferenceOne`

The `ReferenceOne` annotation is a property annotation used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceOne` relationship in the MongoDB ODM.

Required Attributes:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Attributes:

- **identifier** - The name of the property to store the identifier value in

- **inversedBy** - The name of the property on the inverse side of the reference

Example:

```php
<?php
namespace App\Entity;

use App\Document\Article;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Comment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @Gedmo\ReferenceOne(type="document", class="App\Document\Article", inversedBy="comments", identifier="articleId")
     */
    public ?Article $article = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $articleId = null;
}
```

#### `@Gedmo\Mapping\Annotation\ReferenceMany`

The `ReferenceMany` annotation is a property annotation used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceMany` relationship in the MongoDB ODM.

Required Attributes:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Attributes:

- **identifier** - The name of the property to store the identifier value in

- **mappedBy** - The name of the property on the owning side of the reference

Example:

```php
<?php
namespace App\Document;

use App\Entity\Comment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public ?string $id = null;

    /**
     * @var Collection<int, Comment>
     *
     * @Gedmo\ReferenceMany(type="entity", class="App\Entity\Comment", mappedBy="comments")
     */
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

#### `@Gedmo\Mapping\Annotation\ReferenceManyEmbed`

The `ReferenceManyEmbed` annotation is a property annotation used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceMany` relationship in the MongoDB ODM.

Required Attributes:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Attributes:

- **identifier** - The name of the property to store the identifier value in

Example:

```php
<?php
namespace App\Document;

use App\Entity\Comment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public ?string $id = null;

    /**
     * @var Collection<int, Comment>
     *
     * @Gedmo\ReferenceManyEmbed(type="entity", class="App\Entity\Comment", identifier="metadata.commentId")
     */
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

### Sluggable Extension

The below annotations are used to configure the [Sluggable extension](./sluggable.md).

#### `@Gedmo\Mapping\Annotation\Slug`

The `Slug` annotation is a property annotation used to identify the field the slug is stored to.

Required Attributes:

- **fields** - A list of fields on the object to use for generating a slug, this must be a non-empty list of strings

Optional Attributes:

- **updatable** - Flag indicating the slug can be automatically updated if any of the fields have changed,
                  defaults to `true`

- **style** - The style to use while generating the slug, defaults to `default` (no style changes) and ignores
              unsupported styles; supported styles are:
    - `camel` - Converts the slug to a camel-case string
    - `lower` - Converts the slug to a fully lowercased string
    - `upper` - Converts the slug to a fully uppercased string

- **unique** - Flag indicating the slug must be unique, defaults to `true`

- **unique_base** - The name of the object property that should be used as a key when doing a uniqueness check,
                    can only be set when the **unique** flag is `true`

- **separator** - The separator to use between words in the slug, defaults to `-`

- **prefix** - An optional prefix for the generated slug

- **suffix** - An optional suffix for the generated slug

- **handlers** - A list of `@Gedmo\Mapping\Annotation\SlugHandler` annotations used to further customize the slug
                 generator behavior

Basic Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=128)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(fields={"title"})
     */
    public ?string $slug = null;
}
```

#### `@Gedmo\Mapping\Annotation\SlugHandler`

The `SlugHandler` annotation is used with the `Slug` annotation's **handlers** attribute to configure slug handlers for
the object. Slug handlers can be used to further manipulate and validate the generated slug. Please see the
[using slug handlers](./sluggable.md#using-slug-handlers) section of the documentation for more information on how
to use these handlers.

Required Attributes:

- **class** - The class name of a `Gedmo\Sluggable\Handler\SlugHandlerInterface` implementation to use as a handler

Optional Attributes:

- **options** - A list of `@Gedmo\Mapping\Annotation\SlugHandlerOption` annotations used to configure the
                slug handler's behavior

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="articles")
     */
    public ?Category $category = null;

    /**
     * @ORM\Column(type="string", length=128)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(
     *   fields={"title"},
     *   handlers={
     *     @Gedmo\SlugHandler(
     *       class="Gedmo\Sluggable\Handler\TreeSlugHandler",
     *       options={
     *         @Gedmo\SlugHandlerOption(name="parentRelationField", value="category"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *       }
     *     )
     *   }
     * )
     */
    public ?string $slug = null;
}
```

#### `@Gedmo\Mapping\Annotation\SlugHandlerOption`

The `SlugHandlerOption` annotation is used with the `SlugHandler` annotation's **options** attribute to configure
the slug handler.

Required Attributes:

- **name** - The name of the option for the slug handler, must be a non-empty string

Optional Attributes:

- **value** - A value for the option, defaults to null

### Soft Deleteable Extension

The below annotations are used to configure the [Soft Deleteable extension](./softdeleteable.md).

#### `@Gedmo\Mapping\Annotation\SoftDeleteable`

The `SoftDeleteable` annotation is a class annotation used to identify objects which are soft deleteable.

Required Attributes:

- **fieldName** - The name of the property in which the soft delete timestamp is stored, defaults to `deletedAt`;
                  this field must be a field support a `DateTimeInterface`

Optional Attributes:

- **timeAware** - Flag indicating the object supports scheduled soft deletes, defaults to `false`

- **hardDelete** - Flag indicating the object supports hard deletes, defaults to `true`

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable
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
     * @ORM\Column(type="datetime_immutable")
     */
    public ?\DateTimeImmutable $deletedAt = null;
}
```

### Sortable Extension

The below annotations are used to configure the [Sortable extension](./sortable.md).

#### `@Gedmo\Mapping\Annotation\SortableGroup`

The `SortableGroup` annotation is a property annotation used to identify fields which are used to group objects of
this type for sorting.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="articles")
     * @Gedmo\SortableGroup
     */
    public ?Category $category = null;
}
```

#### `@Gedmo\Mapping\Annotation\SortablePosition`

The `SortablePosition` annotation is a property annotation used to identify the field where the sorted position
(optionally within a group) is stored for the current object type. This field must be an integer field type.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="integer")
     * @Gedmo\SortablePosition
     */
    public ?int $position = null;
}
```

### Timestampable Extension

The below annotations are used to configure the [Timestampable extension](./timestampable.md).

#### `@Gedmo\Mapping\Annotation\Timestampable`

The `Timestampable` annotation is a property annotation used to identify fields which are updated to record the
timestamp of the update the mapped object. A timestampable field must be a field supporting a `DateTimeInterface`.

Required Attributes:

- **on** - By default, the annotation configures the property to be updated when an object is updated;
  this can be set to one of \[`change`, `create`, `update`\]

Optional Attributes:

- **field** - An optional list of properties to limit updates to the timestampable field; this option is only
  used when the **on** option is set to "change" and can be a dot separated path to indicate
  properties on a related object are watched (i.e. `user.email` to reference the `$email` property
  of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the timestampable field;
  this option is only used when the **on** option is set to "change"

> [!WARNING]
> When both the **field** and **value** options are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=128)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $body = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="create")
     */
    public ?\DateTimeImmutable $createdAt = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="update")
     */
    public ?\DateTimeImmutable $updatedAt = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    public ?\DateTimeImmutable $contentChangedAt = null;
}
```

### Translatable Extension

The below annotations are used to configure the [Translatable extension](./translatable.md).

#### `@Gedmo\Mapping\Annotation\TranslationEntity`

The `TranslationEntity` annotation is a class annotation used to configure the class used to store translations
for the translatable object.

Required Attributes:

- **class** - The class to use to define translations for this object; defaults to `Gedmo\Translatable\Entity\Translation`
              for Doctrine ORM users or `Gedmo\Translatable\Document\Translation` for Doctrine MongoDB ODM users

> [!TIP]
> Although not strictly required, translation classes are encouraged to extend from the `AbstractPersonalTranslation` or `AbstractTranslation` classes in the `Gedmo\Translatable\<type>\MappedSuperclass` namespace

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="App\Entity\ArticleTranslation")
 */
class Article {}
```

#### `@Gedmo\Mapping\Annotation\Translatable`

The `Translatable` annotation is a property annotation used to mark a field as being translatable.

Optional Attributes:

- **fallback** - When set to true, indicates this field should use the content of the original object if a translation
                 is not available

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="App\Entity\ArticleTranslation")
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
     * @ORM\Column(type="string", length=128)
     * @Gedmo\Translatable
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\Translatable(fallback=true)
     */
    public ?string $body = null;
}
```

#### `@Gedmo\Mapping\Annotation\Locale`

The `Locale` annotation is a property annotation used to indicate the field that the translation locale is stored on.
This field must not be a mapped property (i.e. no `@ORM\Column` annotation).

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="App\Entity\ArticleTranslation")
 */
class Article
{
    /**
     * @Gedmo\Locale
     */
    public ?string $locale = null;
}
```

#### `@Gedmo\Mapping\Annotation\Language`

The `Language` annotation is an alias of the `Locale` annotation.

### Tree Extension

The below annotations are used to configure the [Tree extension](./tree.md).

#### All Tree Strategies

##### `@Gedmo\Mapping\Annotation\Tree`

The `Tree` annotation is a class annotation used to identify objects which are part of a tree implementation,
all tree objects **MUST** have this annotation.

Required Attributes:

- **type** - The type of tree represented by this object, defaults to `nested`; supported values are:
             \[`closure`, `materializedPath`, `nested`\]

> [!WARNING]
> Only the `materializedPath` tree type is supported for the MongoDB ODM at this time

Optional Attributes:

- **activateLocking** - Indicates that a materialized path tree should be locked during write transactions,
                        defaults to true

- **lockingTimeout** - The time (in seconds) for the lock timeout, defaults to 3

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree
 */
class Category {}
```

##### `@Gedmo\Mapping\Annotation\TreeParent`

The `TreeParent` annotation is a property annotation used to identify the relationship for a tree object to its parent.
All tree objects **MUST** have this annotation and the annotation must be defined on a many-to-one relationship.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class Category 
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Gedmo\TreeParent
     */
    public ?self $parent = null;
}
```

#### Closure Tree Strategy

> [!WARNING]
> This strategy is only usable with the Doctrine ORM

##### `@Gedmo\Mapping\Annotation\TreeClosure`

The `TreeClosure` annotation is a class annotation used to configure a closure domain object
for a closure tree strategy.

Required Attributes:

- **class** - The class to be used for the closure domain object, this must be a
              subclass of `Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure`

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 * @Gedmo\Tree
 * @Gedmo\TreeClosure(class="App\Entity\CategoryClosure")
 */
class Category {}
```

#### Materialized Path Strategy

##### `@Gedmo\Mapping\Annotation\TreePath`

The `TreePath` annotation is a property annotation used to identify the property that the calculated path is stored in.

Optional Attributes:

- **separator** - The separator used to separate path segments, defaults to `,`

- **appendId** - Flag indicating the object ID should be appended to the computed path, defaults to `null`

- **startsWithSeparator** - Flag indicating the path should begin with a separator, defaults to `false`

- **endsWithSeparator** - Flag indicating the path should end with a separator, defaults to `true`

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class Category 
{
    /**
     * @ORM\Column(type="string")
     * @Gedmo\TreePath
     */
    public ?string $path = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreePathSource`

The `TreePathSource` annotation is a property annotation used to identify the property that the tree path is
calculated from.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class Category 
{
    /**
     * @ORM\Column(type="string")
     * @Gedmo\TreePathSource
     */
    public ?string $title = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreePathHash`

The `TreePathHash` annotation is a property annotation used to identify the property that a hash of the tree path is
stored in.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class Category 
{
    /**
     * @ORM\Column(type="string")
     * @Gedmo\TreePathHash
     */
    public ?string $pathHash = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreeLockTime`

> [!WARNING]
> This annotation is only usable with the Doctrine MongoDB ODM

The `TreeLockTime` annotation is a property annotation used to identify the property that the tree lock time is
stored in. This must be a date field.

Example:

```php
<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
class Category 
{
    /**
     * @ODM\Field(type="date")
     * @Gedmo\TreeLockTime
     */
    public ?\DateTime $treeLockTime = null;
}
```

#### Nested Tree Strategy

> [!WARNING]
> This strategy is only usable with the Doctrine ORM

##### `@Gedmo\Mapping\Annotation\TreeRoot`

The `TreeRoot` annotation is a property annotation used to identify the relationship for a tree object to its root node.
This is an optional annotation for nested trees which improves performance and allows supporting multiple trees within
a single table, and when used, the annotation must be defined on a many-to-one relationship.

This annotation will use an **integer** type field to specify the root of tree. This way
updating tree will cost less because each root will act as separate tree.

Optional Attributes:

- **identifierMethod** - Allows specifying a method on the related object to call to retrieve the identifier;
                         when not configured, the root property value will be used

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class Category 
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Gedmo\TreeRoot
     */
    public ?self $root = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreeLeft`

The `TreeLeft` annotation is a property annotation used to identify the field used to track the left value for a
nested tree. This annotation **MUST** be set on a property when using the nested tree strategy and should use an
integer field for its value.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class Category 
{
    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeLeft
     */
    public ?int $lft = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreeRight`

The `TreeRight` annotation is a property annotation used to identify the field used to track the right value for a
nested tree. This annotation **MUST** be set on a property when using the nested tree strategy and should use an
integer field for its value.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class Category 
{
    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeRight
     */
    public ?int $rgt = null;
}
```

##### `@Gedmo\Mapping\Annotation\TreeLevel`

The `TreeLevel` annotation is a property annotation used to identify the field used to track the level for a
nested tree. This is an optional annotation for nested trees, and when used, should use an integer field for its value.

Optional Attributes:

- **base** - Allows configuring the base level for objects in this tree, defaults to 0

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class Category 
{
    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeLevel
     */
    public ?int $lvl = null;
}
```

### Uploadable Extension

The below annotations are used to configure the [Uploadable extension](./uploadable.md).

#### `@Gedmo\Mapping\Annotation\Uploadable`

The `Uploadable` annotation is a class annotation used to identify objects which can store information about
uploaded files.

Optional Attributes:

- **allowOverwrite** - Flag indicating that an existing uploaded file can be overwritten, defaults to `false`

- **appendNumber** - Flag indicating that a number should be appended to the filename when the **allowOverwrite** 
                     attribute is `true` and a file already exists, defaults to `false`

- **path** - The file path to store files for this uploadable at; this must be configured unless the **pathMethod**
             attribute is configured or a default path is set on the uploadable listener

- **pathMethod** - A method name on this class to use to retrieve the file path to store files for this uploadable;
                   this must be configured unless the **path** attribute is configured or a default path is set on
                   the uploadable listener

- **callback** - A method name on this class to use to call after the file has been moved

- **filenameGenerator** - A filename generator to use when moving the uploaded file to be used to normalize/customize
                          the file name and defaults to `NONE`; supported styles are:
    - `ALPHANUMERIC` - Normalizes the filename, leaving only alphanumeric characters
    - `NONE` - No conversion
    - `SHA1` - Creates a SHA1 hash of the filename
    - A class name of a class implementing `Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface`

- **maxSize** - An optional maximum file size for this uploadable object; defaults to `0`

- **allowedTypes** - An optional list of allowed MIME types for this uploadable object;
                     cannot be set at the same time as the **disallowedTypes** attribute

- **disallowedTypes** - An optional list of disallowed MIME types for this uploadable object
                        cannot be set at the same time as the **allowedTypes** attribute

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class ArticleAttachment {}
```

#### `@Gedmo\Mapping\Annotation\UploadableFileMimeType`

The `UploadableFileMimeType` annotation is a property annotation used to identify the field that the uploadable's
MIME type is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class ArticleAttachment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\UploadableFileMimeType
     */
    public ?string $mimeType = null;
}
```

#### `@Gedmo\Mapping\Annotation\UploadableFileName`

The `UploadableFileName` annotation is a property annotation used to identify the field that the uploadable's
file name is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class ArticleAttachment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\UploadableFileName
     */
    public ?string $name = null;
}
```

#### `@Gedmo\Mapping\Annotation\UploadableFilePath`

The `UploadableFilePath` annotation is a property annotation used to identify the field that the uploadable's
file path is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class ArticleAttachment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\UploadableFilePath
     */
    public ?string $path = null;
}
```

#### `@Gedmo\Mapping\Annotation\UploadableFileSize`

The `UploadableFileSize` annotation is a property annotation used to identify the field that the uploadable's
file size is stored to. This must be a decimal field.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class ArticleAttachment
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    public ?int $id = null;

    /**
     * @ORM\Column(type="decimal")
     * @Gedmo\UploadableFileSize
     */
    public ?string $size = null;
}
```
