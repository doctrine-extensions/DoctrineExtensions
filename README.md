# Doctrine2 behavioral extensions

**Version 2.3.1-DEV**

[![Build Status](https://secure.travis-ci.org/l3pp4rd/DoctrineExtensions.png?branch=master)](http://travis-ci.org/l3pp4rd/DoctrineExtensions)

**Note:** tag **2.2.1** was removed, because it was published with backward incompatible changes by
misstake. If you have used **2.2.1** tag as a dependency version, please switch to **2.3.0** it is
a dirrect replacement of the removed tag.

**Note:** Use 2.1.x or 2.2.x tag in order to use extensions based on Doctrine2.x.x component versions. Currently
master branch is based on 2.3.x versions and may not work with older components.

### Latest updates

**2012-03-04**

- We should be very grateful for contributions of [comfortablynumb](http://github.com/comfortablynumb)
He has contributed most to these extensions and recently - long waited [softdeleteable
behavior](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/softdeleteable.md) for **ORM** users. Also most important, there
was a tree extension missing for **ODM** now everyone can enjoy [materialized path tree strategy](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/tree.md#materialized-path) for **ORM** including.

**2012-02-26**

- Removed slug handlers, this functionality brought complucations which could not be maintained.

**2012-02-15**

- Add option to force **Translatable** store translation in default locale like any other.
See [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/translatable.md#advanced-examples)

**2012-01-29**

- Translatable finally has **Personal Translations** which can relate through a real **foreign key**
constraint and be used as a standard doctrine collection. This allows to configure domain
objects anyway you prefere and still enjoy all features **Translatable** provides.
- There were **BC** breaks introduced in **master** branch of extensions which is
based on **doctrine2.3.x** version. If you are not interested in upgrading you can
safely checkout at **2.2.x** or **2.1.x** [tag](http://github.com/l3pp4rd/DoctrineExtensions/tags).
To upgrade your source code follow the [upgrade guide](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/upgrade/2-3-0.md)
- Library now can map only **MappedSuperclass**es which would avoid generation of **ext_**
tables which might not be used. Also it provides [convinient methods](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/lib/Gedmo/DoctrineExtensions.php#L66)
to hook extension metadata drivers into metadata driver chain.
- [Example demo application](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/example/em.php) has a detailed configuration provided, which
explains and shows how extensions can or should be used with **Doctrine2** ORM. To install
it follow the [steps](#example-demo).

### Summary and features

This package contains extensions for Doctrine2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine2 more efficently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine2 and handle the
records being flushed in the behavioral way. List of extensions:

- **Tree** - this extension automates the tree handling process and adds some tree specific functions on repository.
(**closure**, **nestedset** or **materialized path**)
- **Translatable** - gives you a very handy solution for translating records into diferent languages. Easy to setup, easier to use.
- **Sluggable** - urlizes your specified fields into single unique slug
- **Timestampable** - updates date fields on create, update and even property change.
- **Loggable** - helps tracking changes and history of objects, also supports version managment.
- **Sortable** - makes any document or entity sortable
- **Translator** - explicit way to handle translations
- **Softdeleteable** - allows to implicitly remove records

Currently these extensions support **Yaml**, **Annotation**  and **Xml** mapping. Additional mapping drivers
can be easily implemented using Mapping extension to handle the additional metadata mapping.

**Note:** Please note, that xml mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is http://gediminasm.org/schemas/orm/doctrine-extensions-mapping
So root node now looks like this:

**Note:** Use 2.1.x tag in order to use extensions based on Doctrine2.1.x versions. Currently
master branch is based on 2.2.x versions and may not work with 2.1.x

```
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
- Loggable
- Translator
- Tree (Materialized Path strategy for now)

All these extensions can be nested together and mapped in traditional ways - annotations,
xml or yaml

You can test these extensions on [my blog](http://gediminasm.org/demo "Test doctrine behavioral extensions").
All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") too.
You can also fork or clone this blog from [github repository](https://github.com/l3pp4rd/gediminasm.org)

### Running the tests:

PHPUnit 3.5 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- run: **php bin/vendors.php**
- run: **phpunit -c tests**
- optional - run mongodb in background to complete all tests

<a name="example-demo"></a>

### Running the example:

To setup and run example follow these steps:

- go to the root directory of extensions
- run: **php bin/vendors.php** installs doctrine and required symfony libraries
- edit **example/em.php** and configure your database on top of the file
- run: **./example/bin/console** or **php example/bin/console** for console commands
- run: **./example/bin/console orm:schema-tool:create** to create schema
- run: **php example/run.php** to run example

### Contributors:

Thanks to [everyone participating](http://github.com/l3pp4rd/DoctrineExtensions/contributors) in
the development of these great Doctrine2 extensions!

And especialy ones who create and maintain new extensions:

- Lukas Botsch [lbotsch](http://github.com/lbotsch)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
