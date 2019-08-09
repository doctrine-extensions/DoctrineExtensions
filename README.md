# Doctrine Behavioral Extensions

[![Build Status](https://secure.travis-ci.org/Atlantic18/DoctrineExtensions.png)](http://travis-ci.org/Atlantic18/DoctrineExtensions)
[![Latest Stable Version](https://poser.pugx.org/gedmo/doctrine-extensions/version)](https://packagist.org/packages/gedmo/doctrine-extensions)

This package contains extensions for Doctrine ORM and MongoDB ODM that offer new functionality or tools to use Doctrine
more efficiently. These behaviors can be easily attached to the event system of Doctrine and handle the records being
flushed in a behavioral way.

## Installation

    $ composer require gedmo/doctrine-extensions

* [Symfony 2](/doc/symfony2.md)
* [Symfony 4](/doc/symfony4.md)
* [Laravel 5](https://www.laraveldoctrine.org/docs/1.3/extensions)
* [Zend Framework 2](/doc/zendframework2.md)

## Extensions

#### ORM & MongoDB ODM

- [**Blameable**](/doc/blameable.md) - updates string or reference fields on create, update and even property change with a string or object (e.g. user).
- [**Loggable**](/doc/loggable.md) - helps tracking changes and history of objects, also supports version management.
- [**Sluggable**](/doc/sluggable.md) - urlizes your specified fields into single unique slug
- [**Timestampable**](/doc/timestampable.md) - updates date fields on create, update and even property change.
- [**Translatable**](/doc/translatable.md) - gives you a very handy solution for translating records into different languages. Easy to setup, easier to use.
- [**Tree**](/doc/tree.md) - automates the tree handling process and adds some tree-specific functions on repository.
(**closure**, **nested set** or **materialized path**)
  _(MongoDB ODM only supports materialized path)_

#### ORM Only

- [**IpTraceable**](/doc/ip_traceable.md) - inherited from Timestampable, sets IP address instead of timestamp
- [**SoftDeleteable**](/doc/softdeleteable.md) - allows to implicitly remove records
- [**Sortable**](/doc/sortable.md) - makes any document or entity sortable
- [**Uploadable**](/doc/uploadable.md) - provides file upload handling in entity fields

#### MongoDB ODM Only

- [**References**](/doc/references.md) - supports linking Entities in Documents and vice versa
- [**ReferenceIntegrity**](/doc/reference_integrity.md) - constrains ODM MongoDB Document references

All extensions support **YAML**, **Annotation** and **XML** mapping. Additional mapping drivers
can be easily implemented using Mapping extension to handle the additional metadata mapping.

### Version Compatibility

| Extensions Version | Compatible Doctrine ORM & Common Library |
| --- | --- |
| 2.4 | 2.5+ |
| 2.3 | 2.2 - 2.4 |

If you are setting up the Entity Manager without a framework, see the [the example](/example/em.php) to prevent issues like #1310

### XML Mapping

XML mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is http://gediminasm.org/schemas/orm/doctrine-extensions-mapping
So root node now looks like this:

```xml
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                 xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
...
</doctrine-mapping>
```

XML mapping xsd schemas are also versioned and can be used by version suffix:

- Latest version - **http://gediminasm.org/schemas/orm/doctrine-extensions-mapping**
- 2.2.x version - **http://gediminasm.org/schemas/orm/doctrine-extensions-mapping-2-2**
- 2.1.x version - **http://gediminasm.org/schemas/orm/doctrine-extensions-mapping-2-1**

### Running Tests

**pdo-sqlite** extension is necessary.

To set up and run the tests, follow these steps:

- go to the root directory of extensions
- download composer: `wget https://getcomposer.org/composer.phar`
- install dev libraries: `php composer.phar install`
- run: `bin/phpunit -c tests`
- optional - run mongodb service if targeting mongo tests

### Running the Example

To set up and run example, follow these steps:

- go to the root directory of extensions
- download composer: `wget https://getcomposer.org/composer.phar`
- install dev libraries: `php composer.phar install`
- edit `example/em.php` and configure your database on top of the file
- run: `./example/bin/console` or `php example/bin/console` for console commands
- run: `./example/bin/console orm:schema-tool:create` to create schema
- run: `php example/run.php` to run example

### Contributors

Thanks to [everyone participating](http://github.com/l3pp4rd/DoctrineExtensions/contributors) in
the development of these great Doctrine extensions!

And especially ones who create and maintain new extensions:

- Lukas Botsch [lbotsch](http://github.com/lbotsch)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- David Buchmann [dbu](https://github.com/dbu)
