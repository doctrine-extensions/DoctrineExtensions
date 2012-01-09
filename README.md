# Doctrine2 behavioral extensions

**Version 2.2-DEV**

### Latest updates

**Note:** Use 2.1.x tag in order to use extensions based on Doctrine2.1.x versions. Currently
master branch is based on 2.2.x versions and may not work with 2.1.x components.

**2012-01-09**

- My [blog](http://gediminasm.org) was recently rebuilt from scratch using symfony2.
All recent documentation based on [extension docs](https://github.com/l3pp4rd/DoctrineExtensions/tree/master/doc)
will be available there too. Also it will be a good example for symfony2 users.

**2012-01-04**

- Refactored translatable to be able to persist, update many translations
using repository, [issue #224](https://github.com/l3pp4rd/DoctrineExtensions/issues/224)

**2011-12-20**

- Refactored html tree building function, see [documentation](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/tree.md)
- Added [example](https://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to create entity manager with extensions hooked using environment without framework
- To run this example follow the documentation on the bottom of this document

**2011-10-30**

- Support for doctrine common **2.2.x** with backward compatibility. Be sure to use all components
from the specific version, etc.: **2.2.x** or **2.1.x** both are supported

**2011-10-23**

- [@everzet](https://github.com/everzet) has contributed the **Translator** behavior, which indeed
is a more explicit way of handling translations in your projects

### Summary and features

This package contains extensions for Doctrine2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine2 more efficently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine2 and handle the
records being flushed in the behavioral way. List of extensions:

- Tree - this extension automates the tree handling process and adds some tree specific functions on repository. (closure or nestedset)
- Translatable - gives you a very handy solution for translating records into diferent languages. Easy to setup, easier to use.
- Sluggable - urlizes your specified fields into single unique slug
- Timestampable - updates date fields on create, update and even property change.
- Loggable - helps tracking changes and history of objects, also supports version managment.
- Sortable - makes any document or entity sortable
- Translator - explicit way to handle translations

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

### Running the example:

To setup and run example follow these steps:

- go to the root directory of extensions
- run: **php bin/vendors.php** installs doctrine and required symfony libraries
- edit **example/em.php** and configure your database on top of the file
- run: **./example/bin/console** or **php example/bin/console** for console commands
- run: **./example/bin/console orm:schema-tool:create** to create schema
- run: **php example/run.php** to run example

### Contributors:

- acasademont [acasademont](https://github.com/acasademont)
- Lukas Botsch [lbotsch](http://github.com/lbotsch)
- Daniel Gomes [danielcsgomes](http://github.com/danielcsgomes)
- megabite [oscarballadares](http://github.com/oscarballadares)
- DinoWeb [dinoweb](http://github.com/dinoweb)
- Miha Vrhovnik [mvrhov](http://github.com/mvrhov)
- Clément JOBEILI [dator](http://github.com/dator)
- Illya Klymov [xanf](http://github.com/xanf)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Christophe Coevoet [stof](http://github.com/stof)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- Klein Florian [docteurklein](http://github.com/docteurklein)
