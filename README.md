# Doctrine2 behavioral extensions

## I'm looking for maintainers of this project

Feel free to open discusion in issue or email message if you are interested in maintaining,
refactoring doctrine2 extensions. The repository can be moved to the maintainers account and fork
left on mine. I do not want users to lose availability of stable extensions which they were and are
used to, at the moment.

**Version 2.3.6**

[![Build Status](https://secure.travis-ci.org/l3pp4rd/DoctrineExtensions.png?branch=master)](http://travis-ci.org/l3pp4rd/DoctrineExtensions)

**Note:** recently doctrine orm and odm were updated to use common doctrine mapping persistense
layer. The support for it has been made and tagged with **2.3.1** version tag. It will be compatible
with latest version of doctrine mapping at master branches

### Latest updates

**2013-03-10**

- **Sluggable** added 'unique_base' configuration parameter

**2013-03-05**

- A new extension - **References**, which links Entities in Documents and visa versa, [read more about it](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/references.md). It was contributed by @jwage, @avalanche123, @jmikola and @bobthecow, thanks

**2013-02-05**

- **Sluggable** added back slug handler mapping driver support for yaml and xml.

**2012-12-06**

- **Blameable** extension added to allow setting a username string or user object on fields, with the same options as Timestampable.


**2012-07-05**

- **Mapping** drivers were updated to support latest doctrine versions.

**2012-05-01**

- **Sluggable** now allows to regenerate slug if its set to empty or null. Also it allows to
manually set the slug, in that case it would only transliterate it and ensure uniqueness.

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
- **Blameable** - updates string or reference fields on create, update and even property change with a string or object (e.g. user).
- **Loggable** - helps tracking changes and history of objects, also supports version managment.
- **Sortable** - makes any document or entity sortable
- **Translator** - explicit way to handle translations
- **Softdeleteable** - allows to implicitly remove records
- **Uploadable** - provides file upload handling in entity fields
- **References** - supports linking Entities in Documents and visa versa

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
- Blameable
- Loggable
- Translator
- Tree (Materialized Path strategy for now)
- References

All these extensions can be nested together and mapped in traditional ways - annotations,
xml or yaml

You can test these extensions on [my blog](http://gediminasm.org/demo "Test doctrine behavioral extensions").
All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") too.
You can also fork or clone this blog from [github repository](https://github.com/l3pp4rd/gediminasm.org)

### Running the tests:

PHPUnit 3.6 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- download composer: **wget https://getcomposer.org/composer.phar**
- install dev libraries: **php composer.phar install --dev**
- run: **phpunit -c tests**
- optional - run mongodb service if targetting mongo tests

<a name="example-demo"></a>

### Running the example:

To setup and run example follow these steps:

- go to the root directory of extensions
- download composer: **wget https://getcomposer.org/composer.phar**
- install dev libraries: **php composer.phar install --dev**
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
- David Buchmann [dbu](https://github.com/dbu)

