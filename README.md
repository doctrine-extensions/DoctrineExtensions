# Doctrine2 behavioral extensions

[![Build Status](https://secure.travis-ci.org/Atlantic18/DoctrineExtensions.png)](http://travis-ci.org/Atlantic18/DoctrineExtensions)
[![Latest Stable Version](https://poser.pugx.org/gedmo/doctrine-extensions/version)](https://packagist.org/packages/gedmo/doctrine-extensions)

**Note:** extensions might not evolve more after **2.4.x** it will remain stable and backward compatible. Unless
new interested maintainers will take over the development and continue with **3.x** versions onward.

**Note:** Extensions **2.4.x** are compatible with ORM and doctrine common library versions from **2.2.x** to **2.5.x**.
ORM 2.5.x versions require **PHP 5.4** or higher.

**Note:** Extensions **2.3.x** are compatible with ORM and doctrine common library versions from **2.2.x** to **2.4.x**
**Note:** If you are setting up entity manager without a framework, see the [example](/example/em.php) to prevent issues like #1310

### Latest updates

**2016-01-27**

- Nested tree now allows root field as association.

**2015-05-01**

- Reverted back [1272](https://github.com/Atlantic18/DoctrineExtensions/pull/1272) and see [1263](https://github.com/Atlantic18/DoctrineExtensions/issues/1263). Use [naming strategy](http://stackoverflow.com/questions/12702657/how-to-configure-naming-strategy-in-doctrine-2) for your use cases.
- Fixed bug for sortable [1279](https://github.com/Atlantic18/DoctrineExtensions/pull/1279)

**2015-03-26**

Support for ORM and Common library **2.5.0**. A minor version bump, because of trait column changes.

**2015-01-28**

Fixed the issue for all mappings, which caused related class mapping failures, when a relation or class name
was in the same namespace, but extensions required it to be mapped as full classname.

**2015-01-21**

Fixed memory leak issue with entity or document wrappers for convenient metadata retrieval.

### Summary and features

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

Currently these extensions support **Yaml**, **Annotation**  and **Xml** mapping. Additional mapping drivers
can be easily implemented using Mapping extension to handle the additional metadata mapping.

**Note:** Please note, that xml mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is http://gediminasm.org/schemas/orm/doctrine-extensions-mapping
So root node now looks like this:

**Note:** Use 2.1.x tag in order to use extensions based on Doctrine2.1.x versions. Currently
master branch is based on 2.2.x versions and may not work with 2.1.x

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

- go to the root directory of extensions
- download composer: `wget https://getcomposer.org/composer.phar`
- install dev libraries: `php composer.phar install`
- run: `bin/phpunit -c tests`
- optional - run mongodb service if targeting mongo tests

### Running the example:

To setup and run example follow these steps:

- go to the root directory of extensions
- download composer: `wget https://getcomposer.org/composer.phar`
- install dev libraries: `php composer.phar install`
- edit `example/em.php` and configure your database on top of the file
- run: `./example/bin/console` or `php example/bin/console` for console commands
- run: `./example/bin/console orm:schema-tool:create` to create schema
- run: `php example/run.php` to run example

### Contributors:

Thanks to [everyone participating](http://github.com/l3pp4rd/DoctrineExtensions/contributors) in
the development of these great Doctrine2 extensions!

And especially ones who create and maintain new extensions:

- Lukas Botsch [lbotsch](http://github.com/lbotsch)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- David Buchmann [dbu](https://github.com/dbu)

