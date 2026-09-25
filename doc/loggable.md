# Loggable Behavior Extension for Doctrine

The **Loggable** behavior adds support for logging changes to and restoring prior versions of your Doctrine objects.

> [!NOTE]
> The `data` column in the log entry table was changed from PHP's `serialize()` format to JSON in order to be compatible with `doctrine/dbal` 4.0 or later. If you are upgrading from an older version, see [Migrating existing serialized data to JSON](#migrating-existing-serialized-data-to-json) below.

## Index

- [Getting Started](#getting-started)
- [Configuring Loggable Objects](#configuring-loggable-objects)
- [Customizing The Log Entry Model](#customizing-the-log-entry-model)
- [Object Repositories](#object-repositories)
    - [Fetching a Model's Log Entries](#fetching-a-models-log-entries)
    - [Revert a Model to a Previous Version](#revert-a-model-to-a-previous-version)
- [Migrating Existing Serialized Data to JSON](#migrating-existing-serialized-data-to-json)

## Getting Started

The loggable behavior can be added to a supported Doctrine object manager by registering its event subscriber
when creating the manager.

```php
use Gedmo\Loggable\LoggableListener;

$listener = new LoggableListener();

// The $om is either an instance of the ORM's entity manager or the MongoDB ODM's document manager
$om->getEventManager()->addEventSubscriber($listener);
```

Then, once your application has it available (i.e. after validating the authentication for your user during an HTTP request),
you can set a reference to the user who performed actions on a loggable model.

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

## Configuring Loggable Objects

The loggable extension can be configured with [annotations](./annotations.md#loggable-extension),
[attributes](./attributes.md#loggable-extension), or XML configuration (matching the mapping of
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
#[Gedmo\Loggable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $published = false;

    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Versioned]
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
            <gedmo:versioned/>
        </field>

        <gedmo:loggable/>
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
 * @Gedmo\Loggable
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
     * @Gedmo\Versioned
     */
    public ?string $title = null;
}
```

## Customizing The Log Entry Model

When configuring loggable models, you are able to specify a custom model to be used for the log entries for objects
of that type using the `logEntryClass` parameter:

### Attribute Configuration

```php
<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[Gedmo\Loggable(logEntryClass: ArticleLogEntry::class)]
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

        <gedmo:loggable log-entry-class="App\Model\ArticleLogEntry"/>
    </entity>
</doctrine-mapping>
```

A custom model must implement `Gedmo\Loggable\LogEntryInterface`. For convenience, we recommend extending from
`Gedmo\Loggable\Entity\MappedSuperClass\AbstractLogEntry` for Doctrine ORM users or
`Gedmo\Loggable\Document\MappedSuperClass\AbstractLogEntry` for Doctrine MongoDB ODM users, which provides a default
mapping configuration for each object manager.

## Object Repositories

The loggable extension includes a `Doctrine\Persistence\ObjectRepository` implementation for each supported object manager
that provides out-of-the-box features for all log entry models. When creating custom models, you are welcome to extend
from either `Gedmo\Loggable\Entity\Repository\LogEntryRepository` for Doctrine ORM users or
`Gedmo\Loggable\Document\Repository\LogEntryRepository` for Doctrine MongoDB ODM users to provide these features.

### Fetching a Model's Log Entries

The repository classes provide a `getLogEntries` method which allows fetching the list of log entries for a given model.

```php
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Loggable\LoggableListener;

/** @var EntityManagerInterface $em */

// Load our loggable model
$article = $em->find(Article::class, 1);

// Next, get the LogEntry repository
/** @var LogEntryRepository $repo */
$repo = $em->getRepository(LogEntry::class);

// Lastly, get the article's log entries
$logs = $repo->getLogEntries($article);
```

### Revert a Model to a Previous Version

The repository classes provide a `revert` method which allows reverting a model to a previous version. The repository
will incrementally revert back to the version specified (for example, a model is currently on version 5, and you want to
revert to version 2, it will restore the state of version 4, then version 3, and finally, version 2).

```php
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Loggable\LoggableListener;

/** @var EntityManagerInterface $em */

// Load our loggable model
$article = $em->find(Article::class, 1);

// Next, get the LogEntry repository
/** @var LogEntryRepository $repo */
$repo = $em->getRepository(LogEntry::class);

// We are now able to revert to an older version
$repo->revert($article, 2);
```

## Migrating Existing Serialized Data to JSON

If you have an existing `ext_log_entries` table (or custom ORM log entry tables) with data stored in the old
PHP `serialize()` format, you need to migrate it to JSON before using `doctrine/dbal` 4.0 or later.

The project provides a Symfony console command for this migration. The command:

1. Renames the existing `data` column to `data_serialized` (so no existing data is lost).
2. Adds a new `data` column with a JSON-compatible type.
3. Reads each row, calls `unserialize()` on the old value and `json_encode()` on the result, and writes it into the new column.
4. Optionally drops the `data_serialized` backup column automatically when `--drop-legacy` is passed. Without this flag the column is kept so you can verify the migration manually before dropping it.

### Usage (Symfony command)

```bash
php bin/console gedmo:loggable:migrate-data-to-json \
    [--batch-size=500] \
    [--drop-legacy]
```

The command uses the injected Doctrine DBAL connection and resolves the mapped ORM log entry table(s)
from Doctrine metadata, so you do not need to pass credentials or table names on the command line.

> [!NOTE]
> Symfony does not automatically discover command services from vendor packages by the `#[AsCommand]` attribute alone.
> If you integrate this library directly, register the command as a service yourself or use a Symfony bundle/recipe that imports vendor services for you. For example:
>
> ```yaml
> services:
>     Gedmo\Loggable\Command\MigrateDataToJsonCommand: ~
> ```

| Option | Description |
|---|---|
| `--batch-size` | Number of rows to process per database round-trip (default: `500`). |
| `--drop-legacy` | Drop the `data_serialized` backup column automatically after a successful migration. Omit this flag if you want to keep the backup column for manual verification first. |

> [!NOTE]
> Run this script **before** updating the entity mapping to use the `json` type and before upgrading to DBAL 4.
> Make a database backup before running the migration.

### Manual migration approach

If you prefer to handle the migration yourself, the steps are:

1. **Rename** the `data` column to `data_serialized`:
   ```sql
   ALTER TABLE ext_log_entries RENAME COLUMN data TO data_serialized;
   ```

2. **Add** a new `data` column (use `LONGTEXT` for MySQL/MariaDB or `TEXT` for SQLite/PostgreSQL):
   ```sql
   ALTER TABLE ext_log_entries ADD COLUMN data LONGTEXT DEFAULT NULL;
   ```

3. **Convert** each row using PHP (the `unserialize()` → `json_encode()` round-trip):

   ```php
   $rows = $connection->fetchAllAssociative('SELECT id, data_serialized FROM ext_log_entries WHERE data_serialized IS NOT NULL');
   foreach ($rows as $row) {
       $deserialized = unserialize($row['data_serialized'], ['allowed_classes' => false]);
       $json = json_encode($deserialized, JSON_THROW_ON_ERROR);
       $connection->executeStatement(
           'UPDATE ext_log_entries SET data = ? WHERE id = ?',
           [$json, $row['id']]
       );
   }
   ```

4. **Drop** the legacy column once you have verified the migration:
   ```sql
   ALTER TABLE ext_log_entries DROP COLUMN data_serialized;
   ```
