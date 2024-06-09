# Attributes Reference

PHP 8 adds native support for metadata with its Attributes feature. The Doctrine Extensions library
provides support for mapping metadata using PHP attributes as of version 3.5.

Below you will a reference for attributes supported in this extensions library, which is closely modeled
after the existing [annotation metadata](./annotations.md). There will be introduction on usage with examples.
For more detailed usage of each extension, refer to the extension's documentation page.

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

The below attributes are used to configure the [Blameable extension](./blameable.md).

#### `#[Gedmo\Mapping\Annotation\Blameable]`

The `Blameable` attribute is a property attribute used to identify fields which are updated to show information
about the last user to update the mapped object. A blameable field may have either a string value or a one-to-many
relationship with another entity.

Required Parameters:

- **on** - By default, the attribute configures the property to be updated when an object is updated;
           this can be set to one of \[`change`, `create`, `update`\]

Optional Parameters:

- **field** - An optional list of properties to limit updates to the blameable field; this parameter is only
              used when the **on** parameter is set to "change" and can be a dot separated path to indicate
              properties on a related object are watched (i.e. `user.email` to reference the `$email` property
              of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the blameable field;
              this parameter is only used when the **on** option is set to "change"

> [!WARNING]
> When both the **field** and **value** parameters are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $body = null;

    /**
     * Blameable field storing a username for the user who created the entity
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    public ?string $createdBy = null;

    /**
     * Blameable field storing a User relation for the user who updated the entity
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Gedmo\Blameable(on: 'update')]
    public ?User $updatedBy = null;

    /**
     * Blameable field storing a username for the user who last changed either the title or body fields
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: ['title', 'body'])]
    public ?string $contentChangedBy = null;
}
```

### IP Traceable Extension

The below attributes are used to configure the [IP Traceable extension](./ip_traceable.md).

#### `#[Gedmo\Mapping\Annotation\IpTraceable]`

The `IpTraceable` attribute is a property attribute used to identify fields which are updated to record the
IP address from the last user to update the mapped object. A traceable field must be a string.

Required Parameters:

- **on** - By default, the attribute configures the property to be updated when an object is updated;
           this can be set to one of \[`change`, `create`, `update`\]

Optional Parameters:

- **field** - An optional list of properties to limit updates to the IP traceable field; this parameter is only
              used when the **on** parameter is set to "change" and can be a dot separated path to indicate
              properties on a related object are watched (i.e. `user.email` to reference the `$email` property
              of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the IP traceable field;
              this parameter is only used when the **on** parameter is set to "change"

> [!WARNING]
> When both the **field** and **value** parameters are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $body = null;

    /**
     * Traceable field storing an IP address for the user who created the entity
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable(on: 'create')]
    public ?string $createdByIp = null;

    /**
     * Traceable field storing an IP address for the user who updated the entity
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\IpTraceable(on: 'update')]
    public ?string $updatedByIp = null;

    /**
     * Traceable field storing an IP address for the user who last changed either the title or body fields
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: ['title', 'body'])]
    public ?string $contentChangedByIp = null;
}
```

### Loggable Extension

The below attributes are used to configure the [Loggable extension](./loggable.md).

#### `#[Gedmo\Mapping\Annotation\Loggable]`

The `Loggable` attribute is a class attribute used to identify objects which can have changes logged,
all loggable objects **MUST** have this attribute.

Required Parameters:

- **logEntryClass** - A custom model class implementing `Gedmo\Loggable\LogEntryInterface` to use for logging changes;
                      defaults to `Gedmo\Loggable\Entity\LogEntry` for Doctrine ORM users or
                      `Gedmo\Loggable\Document\LogEntry` for Doctrine MongoDB ODM users

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Loggable(logEntryClass: ArticleLogEntry::class)]
class Article {}
```

#### `#[Gedmo\Mapping\Annotation\Versioned]`

The `Versioned` attribute is a property attribute used to identify properties whose changes should be logged.
This attribute can be set for properties with a single value (i.e. a scalar type or an object such as
`DateTimeInterface`), but not for collections. Versioned fields can be restored to an earlier version.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Loggable]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[Gedmo\Versioned]
    public ?Article $article = null;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Versioned]
    public ?string $body = null;
}
```

### Reference Integrity Extension

The below attributes are used to configure the [Reference Integrity extension](./reference_integrity.md).

> [!WARNING]
> This extension is only usable with the Doctrine MongoDB ODM

#### `#[Gedmo\Mapping\Annotation\ReferenceIntegrity]`

The `ReferenceIntegrity` attribute is a property attribute used to identify fields where referential integrity
should be checked. The attribute must be used on a property which references another document, and the reference
configuration must have a `mappedBy` configuration.

Required Parameters:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

Example:

```php
<?php
namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document(collection: 'articles')]
class Article
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: Type::STRING)]
    public ?string $title = null;

    #[ODM\ReferenceOne(targetDocument: User::class, mappedBy: 'articles')]
    #[Gedmo\ReferenceIntegrity('nullify')]
    public ?User $author = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'article')]
    #[Gedmo\ReferenceIntegrity('nullify')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

### References Extension

The below attributes are used to configure the [References extension](./references.md).

#### `#[Gedmo\Mapping\Annotation\ReferenceOne]`

The `ReferenceOne` attribute is a property attribute used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceOne` relationship in the MongoDB ODM.

Required Parameters:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Parameters:

- **identifier** - The name of the property to store the identifier value in

- **inversedBy** - The name of the property on the inverse side of the reference

Example:

```php
<?php
namespace App\Entity;

use App\Document\Article;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[Gedmo\ReferenceOne(type: 'document', class: Article::class, inversedBy: 'comments', identifier: 'articleId')]
    public ?Article $article = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $articleId = null;
}
```

#### `#[Gedmo\Mapping\Annotation\ReferenceMany]`

The `ReferenceMany` attribute is a property attribute used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceMany` relationship in the MongoDB ODM.

Required Parameters:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Parameters:

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

#[ODM\Document(collection: 'articles')]
class Article
{
    #[ORM\Id]
    public ?string $id = null;

    /**
     * @var Collection<int, Comment>
     */
    #[Gedmo\ReferenceMany(type: 'entity', class: Comment::class, mappedBy: 'comments')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

#### `#[Gedmo\Mapping\Annotation\ReferenceManyEmbed]`

The `ReferenceManyEmbed` attribute is a property attribute used to create a reference between two objects in different
databases or object managers. This is similar to a `ReferenceMany` relationship in the MongoDB ODM.

Required Parameters:

- **value** - The type of action to take for the reference, must be one of \[`nullify`, `pull`, `restrict`\]

- **type** - The type of object manager to use for the reference, must be one of \[`document`, `entity`\]

- **class** - The class name of the object to reference

Optional Parameters:

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

#[ODM\Document(collection: 'articles')]
class Article
{
    #[ORM\Id]
    public ?string $id = null;

    /**
     * @var Collection<int, Comment>
     */
    #[Gedmo\ReferenceManyEmbed(type: 'entity', class: Comment::class, identifier: 'metadata.commentId')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}
```

### Sluggable Extension

The below attributes are used to configure the [Sluggable extension](./sluggable.md).

#### `#[Gedmo\Mapping\Annotation\Slug]`

The `Slug` attribute is a property attribute used to identify the field the slug is stored to.

Required Parameters:

- **fields** - A list of fields on the object to use for generating a slug, this must be a non-empty list of strings

Optional Parameters:

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

- **handlers** - Unused with attributes

Basic Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    public ?string $slug = null;
}
```

#### `#[Gedmo\Mapping\Annotation\SlugHandler]`

The `SlugHandler` attribute is used on a field with the `Slug` attribute to configure slug handlers for
the object. Slug handlers can be used to further manipulate and validate the generated slug. Please see the
[using slug handlers](./sluggable.md#using-slug-handlers) section of the documentation for more information on how
to use these handlers.

Required Parameters:

- **class** - The class name of a `Gedmo\Sluggable\Handler\SlugHandlerInterface` implementation to use as a handler

Optional Parameters:

- **options** - An associative array of options used to configure the slug handler's behavior

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\TreeSlugHandler;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    public ?Category $category = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'category', 'separator' => '/'])]
    public ?string $slug = null;
}
```

#### `#[Gedmo\Mapping\Annotation\SlugHandlerOption]`

The `SlugHandlerOption` attribute is not supported when using attributes for configuration. Instead, the options
can be configured directly in the `SlugHandler` attribute's **options** parameter.

### Soft Deleteable Extension

The below attributes are used to configure the [Soft Deleteable extension](./softdeleteable.md).

#### `#[Gedmo\Mapping\Annotation\SoftDeleteable]`

The `SoftDeleteable` attribute is a class attribute used to identify objects which are soft deleteable.

Required Parameters:

- **fieldName** - The name of the property in which the soft delete timestamp is stored, defaults to `deletedAt`;
                  this field must be a field support a `DateTimeInterface`

Optional Parameters:

- **timeAware** - Flag indicating the object supports scheduled soft deletes, defaults to `false`

- **hardDelete** - Flag indicating the object supports hard deletes, defaults to `true`

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\SoftDeleteable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public ?\DateTimeImmutable $deletedAt = null;
}
```

### Sortable Extension

The below attributes are used to configure the [Sortable extension](./sortable.md).

#### `#[Gedmo\Mapping\Annotation\SortableGroup]`

The `SortableGroup` attribute is a property attribute used to identify fields which are used to group objects of
this type for sorting.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[Gedmo\SortableGroup]
    public ?Category $category = null;
}
```

#### `#[Gedmo\Mapping\Annotation\SortablePosition]`

The `SortablePosition` attribute is a property attribute used to identify the field where the sorted position
(optionally within a group) is stored for the current object type. This field must be an integer field type.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\SortablePosition]
    public ?int $position = null;
}
```

### Timestampable Extension

The below attributes are used to configure the [Timestampable extension](./timestampable.md).

#### `#[Gedmo\Mapping\Annotation\Timestampable]`

The `Timestampable` attribute is a property attribute used to identify fields which are updated to record the
timestamp of the update the mapped object. A timestampable field must be a field supporting a `DateTimeInterface`.

Required Parameters:

- **on** - By default, the attribute configures the property to be updated when an object is updated;
  this can be set to one of \[`change`, `create`, `update`\]

Optional Parameters:

- **field** - An optional list of properties to limit updates to the timestampable field; this parameter is only
  used when the **on** parameter is set to "change" and can be a dot separated path to indicate
  properties on a related object are watched (i.e. `user.email` to reference the `$email` property
  of the `$user` relation on this object)

- **value** - An optional value to require the configured **field** to match to update the timestampable field;
  this parameter is only used when the **on** option is set to "change"

> [!WARNING]
> When both the **field** and **value** parameters are set, the **field** can only be set to a single field; checking the value against multiple fields is not supported at this time

Examples:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING)]
    public ?string $body = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    public ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'change', field: ['title', 'body'])]
    public ?\DateTimeImmutable $contentChangedAt = null;
}
```

### Translatable Extension

The below attributes are used to configure the [Translatable extension](./translatable.md).

#### `#[Gedmo\Mapping\Annotation\TranslationEntity]`

The `TranslationEntity` attribute is a class attribute used to configure the class used to store translations
for the translatable object.

Required Parameters:

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

#[ORM\Entity]
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)
class Article {}
```

#### `#[Gedmo\Mapping\Annotation\Translatable]`

The `Translatable` attribute is a property attribute used to mark a field as being translatable.

Optional Parameters:

- **fallback** - When set to true, indicates this field should use the content of the original object if a translation
                 is not available

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Gedmo\Translatable]
    public ?string $title = null;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Translatable(fallback: true)]
    public ?string $body = null;
}
```

#### `#[Gedmo\Mapping\Annotation\Locale]`

The `Locale` attribute is a property attribute used to indicate the field that the translation locale is stored on.
This field must not be a mapped property (i.e. no `#[ORM\Column]` attribute).

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)
class Article
{
    #[Gedmo\Locale]
    public ?string $locale = null;
}
```

#### `#[Gedmo\Mapping\Annotation\Language]`

The `Language` attribute is an alias of the `Locale` attribute.

### Tree Extension

The below attributes are used to configure the [Tree extension](./tree.md).

#### All Tree Strategies

##### `#[Gedmo\Mapping\Annotation\Tree]`

The `Tree` attribute is a class attribute used to identify objects which are part of a tree implementation,
all tree objects **MUST** have this attribute.

Required Parameters:

- **type** - The type of tree represented by this object, defaults to `nested`; supported values are:
             \[`closure`, `materializedPath`, `nested`\]

> [!WARNING]
> Only the `materializedPath` tree type is supported for the MongoDB ODM at this time

Optional Parameters:

- **activateLocking** - Indicates that a materialized path tree should be locked during write transactions,
                        defaults to true

- **lockingTimeout** - The time (in seconds) for the lock timeout, defaults to 3

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree]
class Category {}
```

##### `#[Gedmo\Mapping\Annotation\TreeParent]`

The `TreeParent` attribute is a property attribute used to identify the relationship for a tree object to its parent.
All tree objects **MUST** have this attribute and the attribute must be defined on a many-to-one relationship.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Category 
{
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    public ?self $parent = null;
}
```

#### Closure Tree Strategy

> [!WARNING]
> This strategy is only usable with the Doctrine ORM

##### `#[Gedmo\Mapping\Annotation\TreeClosure]`

The `TreeClosure` attribute is a class attribute used to configure a closure domain object
for a closure tree strategy.

Required Parameters:

- **class** - The class to be used for the closure domain object, this must be a
              subclass of `Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure`

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

#[ORM\Entity(repositoryClass: ClosureTreeRepository::class)]
#[Gedmo\Tree(type: 'closure')]
#[Gedmo\TreeClosure(class: CategoryClosure::class)]
class Category {}
```

#### Materialized Path Strategy

##### `#[Gedmo\Mapping\Annotation\TreePath]`

The `TreePath` attribute is a property attribute used to identify the property that the calculated path is stored in.

Optional Parameters:

- **separator** - The separator used to separate path segments, defaults to `,`

- **appendId** - Flag indicating the object ID should be appended to the computed path, defaults to `null`

- **startsWithSeparator** - Flag indicating the path should begin with a separator, defaults to `false`

- **endsWithSeparator** - Flag indicating the path should end with a separator, defaults to `true`

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;

#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class Category 
{
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\TreePath]
    public ?string $path = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreePathSource]`

The `TreePathSource` attribute is a property attribute used to identify the property that the tree path is
calculated from.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;

#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class Category 
{
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\TreePathSource]
    public ?string $title = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreePathHash]`

The `TreePathHash` attribute is a property attribute used to identify the property that a hash of the tree path is
stored in.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;

#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class Category 
{
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\TreePathHash]
    public ?string $pathHash = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreeLockTime]`

> [!WARNING]
> This attribute is only usable with the Doctrine MongoDB ODM

The `TreeLockTime` attribute is a property attribute used to identify the property that the tree lock time is
stored in. This must be a date field.

Example:

```php
<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;

#[ODM\Document(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath', activateLocking: true)]
class Category 
{
    #[ODM\Field(type: Type::DATE)]
    #[Gedmo\TreeLockTime]
    public ?\DateTime $treeLockTime = null;
}
```

#### Nested Tree Strategy

> [!WARNING]
> This strategy is only usable with the Doctrine ORM

##### `#[Gedmo\Mapping\Annotation\TreeRoot]`

The `TreeRoot` attribute is a property attribute used to identify the relationship for a tree object to its root node.
This is an optional attribute for nested trees which improves performance and allows supporting multiple trees within
a single table, and when used, the attribute must be defined on a many-to-one relationship.

This attribute will use an **integer** type field to specify the root of tree. This way
updating tree will cost less because each root will act as separate tree.

Optional Parameters:

- **identifierMethod** - Allows specifying a method on the related object to call to retrieve the identifier;
                         when not configured, the root property value will be used

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Category 
{
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Gedmo\TreeRoot]
    public ?self $root = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreeLeft]`

The `TreeLeft` attribute is a property attribute used to identify the field used to track the left value for a
nested tree. This attribute **MUST** be set on a property when using the nested tree strategy and should use an
integer field for its value.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Category 
{
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    public ?int $lft = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreeRight]`

The `TreeRight` attribute is a property attribute used to identify the field used to track the right value for a
nested tree. This attribute **MUST** be set on a property when using the nested tree strategy and should use an
integer field for its value.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Category 
{
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    public ?int $rgt = null;
}
```

##### `#[Gedmo\Mapping\Annotation\TreeLevel]`

The `TreeLevel` attribute is a property attribute used to identify the field used to track the level for a
nested tree. This is an optional attribute for nested trees, and when used, should use an integer field for its value.

Optional Parameters:

- **base** - Allows configuring the base level for objects in this tree, defaults to 0

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Category 
{
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    public ?int $lvl = null;
}
```

### Uploadable Extension

The below attributes are used to configure the [Uploadable extension](./uploadable.md).

#### `#[Gedmo\Mapping\Annotation\Uploadable]`

The `Uploadable` attribute is a class attribute used to identify objects which can store information about
uploaded files.

Optional Parameters:

- **allowOverwrite** - Flag indicating that an existing uploaded file can be overwritten, defaults to `false`

- **appendNumber** - Flag indicating that a number should be appended to the filename when the **allowOverwrite** 
                     parameter is `true` and a file already exists, defaults to `false`

- **path** - The file path to store files for this uploadable at; this must be configured unless the **pathMethod**
             parameter is configured or a default path is set on the uploadable listener

- **pathMethod** - A method name on this class to use to retrieve the file path to store files for this uploadable;
                   this must be configured unless the **path** parameter is configured or a default path is set on
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
                     cannot be set at the same time as the **disallowedTypes** parameter

- **disallowedTypes** - An optional list of disallowed MIME types for this uploadable object
                        cannot be set at the same time as the **allowedTypes** parameter

Example:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Uploadable]
class ArticleAttachment {}
```

#### `#[Gedmo\Mapping\Annotation\UploadableFileMimeType]`

The `UploadableFileMimeType` attribute is a property attribute used to identify the field that the uploadable's
MIME type is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Uploadable]
class ArticleAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\UploadableFileMimeType]
    public ?string $mimeType = null;
}
```

#### `#[Gedmo\Mapping\Annotation\UploadableFileName]`

The `UploadableFileName` attribute is a property attribute used to identify the field that the uploadable's
file name is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Uploadable]
class ArticleAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\UploadableFileName]
    public ?string $name = null;
}
```

#### `#[Gedmo\Mapping\Annotation\UploadableFilePath]`

The `UploadableFilePath` attribute is a property attribute used to identify the field that the uploadable's
file path is stored to.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Uploadable]
class ArticleAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    public ?string $path = null;
}
```

#### `#[Gedmo\Mapping\Annotation\UploadableFileSize]`

The `UploadableFileSize` attribute is a property attribute used to identify the field that the uploadable's
file size is stored to. This must be a decimal field.

Example:

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Uploadable]
class ArticleAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL)]
    #[Gedmo\UploadableFileSize]
    public ?string $size = null;
}
```
