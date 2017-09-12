# Doctrine2 behavioral extensions

**Version 3.0.0**

[![Build Status](https://secure.travis-ci.org/Atlantic18/DoctrineExtensions.png?branch=master)](http://travis-ci.org/Atlantic18/DoctrineExtensions)
[![Total Downloads](https://poser.pugx.org/gedmo/doctrine-extensions/downloads.png)](https://packagist.org/packages/gedmo/doctrine-extensions)
[![Latest Stable Version](https://poser.pugx.org/gedmo/doctrine-extensions/v/stable.png)](https://packagist.org/packages/gedmo/doctrine-extensions)

**Note:** Extensions **3.0.x** are compatible with **ORM** and doctrine common libraries from **2.5.x** and requires **PHP 5.4** or higher.
Extensions **2.4.x** are compatible with ORM and doctrine common library versions from **2.2.x** to **2.5.x**

Since the author has stopped working with PHP, looking for maintainers to ensure stability of doctrine extensions.

### Latest updates

**2016-01-27**

- Nested tree now allows **root** field as association.
- Sortable supports more than one sortable field per entity, has **BC** changes.
- Uploadable supports more than one file per entity, may have implicit **BC** change if users have used their custom **FilenameGeneratorInterface** see [#1342](https://github.com/Atlantic18/DoctrineExtensions/pull/1342).

**2015-12-27**

- From now on, extensions will require **php 5.4** or higher.
- All trait column names will refer to naming strategy and won't be explicitly set by extensions.
- Tree repositories are now using traits, for easier extensions.

### Extensions and Documentation

This package contains extensions for Doctrine2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine2 more efficiently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine2 and handle the
records being flushed in the behavioral way. List of extensions:

- [**Tree**](/doc/tree.md) - this extension automates the tree handling process and adds some tree specific functions on repository.
(**closure**, **nestedset** or **materialized path**)
- [**Translatable**](/doc/translatable.md) - gives you a very handy solution for translating records into different languages. Easy to setup, easier to use.
- [**Sluggable**](/doc/sluggable.md) - urlizes your specified fields into single unique slug
- [**Timestampable**](/doc/timestampable.md) - updates date fields on create, update and even property change.
- [**Blameable**](/doc/blameable.md) - updates string or reference fields on create, update and even property change with a string or object (e.g. user).
- [**Loggable**](/doc/loggable.md) - helps tracking changes and history of objects, also supports version management.
- [**Sortable**](/doc/sortable.md) - makes any document or entity sortable
- [**Translator**](/doc/translatable.md) - explicit way to handle translations
- [**SoftDeleteable**](/doc/softdeleteable.md) - allows to implicitly remove records
- [**Uploadable**](/doc/uploadable.md) - provides file upload handling in entity fields
- [**References**](/doc/references.md) - supports linking Entities in Documents and vice versa
- [**ReferenceIntegrity**](/doc/reference_integrity.md) - constrains ODM MongoDB Document references
- [**IpTraceable**](/doc/ip_traceable.md) - inherited from Timestampable, sets IP address instead of timestamp

Currently these extensions support **Yaml**, **Annotation**  and **Xml** mapping.

**Note:** Please note, that xml mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is [doctrine-extensions.xsd](http://atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions.xsd)
So root node now looks like this:

```xml
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-extensions.xsd"
                 xmlns:gedmo="http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions.xsd">
...
</doctrine-mapping>
```

XML mapping xsd schemas are also versioned and can be used by version suffix:

- Latest version - **http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions.xsd**
- 2.4.x version - **http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions-2.4.xsd**
- 3.0.x version - **http://Atlantic18.github.io/DoctrineExtensions/schemas/orm/doctrine-extensions-3.0.xsd**

### ODM MongoDB support

List of extensions which support ODM

- Translatable
- Sluggable
- Timestampable
- Blameable
- Loggable
- Translator
- Tree (Materialized Path strategy for now)
- References
- ReferenceIntegrity

All these extensions can be nested together and mapped in traditional ways - **annotations**,
**xml** or **yaml**

### Running the tests:

**pdo-sqlite** extension is necessary.
To setup and run tests follow these steps:

- install dev libraries: `composer install`
- run: `bin/phpunit -c tests`
- optional - run mongodb service if targeting mongo tests

**NOTE:** if php7 is used with **mongodb** install extension and dependencies using composer7.json.
This is a temporary hack until the better ODM support is available.

### Running the example:

To setup and run example follow these steps:

- go to the root directory of extensions
- install dev libraries: `composer install`
- edit `example/em.php` and configure your database on top of the file
- run: `./example/bin/console` or `php example/bin/console` for console commands
- run: `./example/bin/console orm:schema-tool:create` to create schema
- run: `php example/run.php` to run example

### Contributors:

**NOTE:** composer7.json is only used to test extensions with ODM mongodb using php7, same for travis.

Thanks to [everyone participating](http://github.com/l3pp4rd/DoctrineExtensions/contributors) in
the development of these great Doctrine2 extensions!

And especially ones who create and maintain new extensions:

- Lukas Botsch [lbotsch](http://github.com/lbotsch)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- David Buchmann [dbu](https://github.com/dbu)

