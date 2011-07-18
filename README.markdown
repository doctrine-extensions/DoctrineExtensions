# Doctrine 2 behavioral extensions

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine 2 and handle the
records being flushed in the behavioral way. List of extensions:

- Tree - this extension automates the tree handling process and adds some tree specific functions on repository. (closure or nestedset)
- Translatable - gives you a very handy solution for translating records into diferent languages. Easy to setup, easier to use.
- Sluggable - urlizes your specified fields into single unique slug
- Timestampable - updates date fields on create, update and even property change.
- Loggable - helps tracking changes and history of objects, also supports version managment.
- Sortable - makes any document or entity sortable

Currently these extensions support **Yaml**, **Annotation**  and **Xml** mapping. Additional mapping drivers
can be easily implemented using Mapping extension to handle the additional metadata mapping.

**Note:** doctrine2.0.x branch is no longer being supported and all new features
and fixes are on master branch, which currently supports 2.1.x versions of doctrine2

**Note:** from now on there is only one listener per extension which supports ODM and ORM adapters to deal with objects. Only one instance of listener is 
required, and can be attached to many different type object managers, currently supported (ORM or ODM)

**Note:** Please note, that xml mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is http://gediminasm.org/schemas/orm/doctrine-extensions-mapping
So root node now looks like this:

```
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                 xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
...
</doctrine-mapping>
```

### Latest updates

**2011-07-14**

- Sluggable can handle multiple slug fields, now on Sluggable annotation you will need
to provide slugField property it should belong to
- Translatable query hint will use **default locale** translations in case if it does not
have a translation in currently used locale

**2011-06-24**

- Implemented better compatibility with new and older annotation mapping style. Read
**doc/annotations.md** on how to achieve best performances using annotation mapping and
how to configure it.
- The object wrappers were implemented internally in extensions to avoid issues with
uninitialized or detached proxies.

**2011-06-08**

- [mvrhov](http://github.com/mvrhov) implemented the XML driver for extensions and now
there is a full stack of drivers to make your experience even better using these extensions.
So far I'm not sure if the same xsd will work with ODM but it will be created in comming month.

### ODM MongoDB support

List of extensions which support ODM

- Translatable
- Sluggable
- Timestampable
- Loggable
- Sortable

All these extensions can be nested together and mapped in traditional ways - annotations,
xml or yaml

**Notice:** extension tutorial on doctrine blog is outdated, most recent documentation is in **doc** directory.
There is a post introducing to these extensions on [doctrine project](http://www.doctrine-project.org/blog/doctrine2-behavioral-extensions "Doctrine2 behavior extensions")

You can test these extensions on [my blog](http://gediminasm.org/test/ "Test doctrine behavior extensions").

All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") also.

### Recommendations

- Use Symfony/Component/ClassLoader/UniversalClassLoader for autoloading these extensions, it will help
to avoid triggering fatal error during the check of **class_exists**

### Running the tests:

PHPUnit 3.5 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- run: **php bin/vendors.php**
- run: **phpunit -c tests**
- optional - run mongodb in background to complete all tests

### Contributors:

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
