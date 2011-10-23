# Doctrine2 behavioral extensions

**Version 2.2-DEV**

### Latest updates

**Note:** Use 2.1.x tag in order to use extensions based on Doctrine2.1.x versions. Currently
master branch is based on 2.2.x versions and may not work with 2.1.x components

**2011-10-23**

- [@everzet](https://github.com/everzet) has contributed the **Translator** behavior, which indeed
is a more explicit way of handling translations in your projects

**2011-10-08**

- Thanks to [@acasademont](https://github.com/acasademont) Translatable now does not store translations for default locale. It is always left as original record value.
So be sure you do not change your default locale per project or per data migration. This way
it is more rational and unnecessary to store it additionaly in translation table.

**2011-09-24**

- Sluggable was refactored with a **BC break** for the sake of simplicity it now uses a single @Slug annotation.
Also there were slug handlers introduced for extended sluggable functionality, like path based
or relation based slugs. See the [documentation](https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/sluggable.md)

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

### ODM MongoDB support

List of extensions which support ODM

- Translatable
- Sluggable
- Timestampable
- Loggable
- Sortable
- Translator

All these extensions can be nested together and mapped in traditional ways - annotations,
xml or yaml

**Notice:** extension tutorial on doctrine blog is outdated, most recent documentation is in **doc** directory.
There is a post introducing to these extensions on [doctrine project](http://www.doctrine-project.org/blog/doctrine2-behavioral-extensions "Doctrine2 behavior extensions")

You can test these extensions on [my blog](http://gediminasm.org/test/ "Test doctrine behavior extensions").

All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") also.

### Running the tests:

PHPUnit 3.5 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- run: **php bin/vendors.php**
- run: **phpunit -c tests**
- optional - run mongodb in background to complete all tests

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
