# Aggregate versioning behavior extension for Doctrine 2

**Aggregate versioning** behavior will automate control the aggregate version of Aggregate Root or reference Aggregate
entity on your Entities. It works through annotations and can update aggregate version on creation, update,
aggregate root or entity subset update. Similar the [aggregate fields](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/cookbook/aggregate-fields.html#using-an-aggregate-field) 
in doctrine docs.

Features:

- Automatic aggregate version field update on creation, update, property subset update, and even on record property changes
- Annotation mapping support for extensions

This article will cover the basic installation and functionality of **Aggregate versioning** behavior

Content:

- Entity [example](#entity-mapping)
- Using [Traits](#traits)

<a name="entity-mapping"></a>

## Aggregate versioning Entity example:

### Aggregate versioning annotations:
- **Gedmo\Mapping\Annotation\Aggregate Versioning** this annotation tells how getting the aggregate root from method . If method is not a exists it will trigger an exception.

Available configuration options:

- **aggregate Root Method** - name method getting aggregate root in from this Entity
- 
**Note:** required entity must implement interface **Gedmo\AggregateVersioning\AggregateEntity**

Aggregate entity:

```php
<?php

declare(strict_types=1);

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\AggregateVersioning\AggregateEntity;
use Gedmo\Mapping\Annotation\AggregateVersioning;

/**
 * @ORM\Entity
 * @ORM\Table(name="order_lines")
 * @AggregateVersioning(aggregateRootMethod="getOrder")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
class OrderLine implements AggregateEntity
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;
    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="items")
     * @ORM\JoinColumn(name="order_id")
     */
    private $order;
    /**
     * @var Line
     *
     * @ORM\Embedded(class="Line", columnPrefix=false)
     */
    private $line;

    public function __construct(Order $order, Line $line)
    {
        $this->order = $order;
        $this->line = $line;
    }

    public function edit(Line $line): void
    {
        $this->line = $line;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getLine(): Line
    {
        return $this->line;
    }
}
```

Aggregate root:

**Note:** required entity must implement interface **Gedmo\AggregateVersioning\AggregateRoot**

```php
<?php

declare(strict_types=1);

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Gedmo\AggregateVersioning\AggregateRoot;
use Gedmo\AggregateVersioning\Traits\AggregateVersioningTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orders")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
class Order implements AggregateRoot
{
    private const STATUS_NEW = 'new';
    private const STATUS_CLOSED = 'closed';
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $status;
    /**
     * @var OrderLine[]|Collection
     * @ORM\OneToMany(targetEntity="OrderLine", mappedBy="order", orphanRemoval=true, cascade={"persist"})
     */
    private $items;
     /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $aggregateVersion;
    /**
     * @var int
     *
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    protected $version;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->status = self::STATUS_NEW;
        $this->items = new ArrayCollection();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
    }

    public function addLine(Line $line): void
    {
        $this->items->add(new OrderLine($this, $line));
    }

    public function updateAggregateVersion(): void
    {
        $this->aggregateVersion++;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return OrderLine[]
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }
}
```

<a name="traits"></a>

## Traits

You can use aggregate versioning traits for quick **aggregateVersion** string definitions
when using annotation mapping.

**Note:** this feature is only available since php **5.4.0**. And you are not required
to use the Traits provided by extensions.

```php
<?php

declare(strict_types=1);

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Gedmo\AggregateVersioning\AggregateRoot;
use Gedmo\AggregateVersioning\Traits\AggregateVersioningTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orders")
 */
class Order implements AggregateRoot
{
    use AggregateVersioningTrait;

    private const STATUS_NEW = 'new';
    private const STATUS_CLOSED = 'closed';
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $status;
    /**
     * @var Collection<int, OrderLine>
     * @ORM\OneToMany(targetEntity="OrderLine", mappedBy="order", orphanRemoval=true, cascade={"persist"})
     */
    private $items;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->status = self::STATUS_NEW;
        $this->items = new ArrayCollection();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
    }

    public function addLine(Line $line): void
    {
        $this->items->add(new OrderLine($this, $line));
    }
    
    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }
}

```

The Traits are very simplistic - if you use different field names it is recommended to simply create your
own Traits specific to your project. The ones provided by this bundle can be used as example.
