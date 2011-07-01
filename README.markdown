# Some Doctrine 2 Extensions

Notice: **This branch is compatible with Doctrine2.0.x versions**
and will be maintained only to support 2.0.x branch. All latest features and
functionality updates are on **master** branch and compatible with upcoming
releases of Doctrine2.1 

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficiently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine 2 and handle the
records being flushed in the behavioral way. List of extensions:

- Tree - this extension automates the tree handling process and adds some tree specific functions on repository. (closure or nestedset)
- Translatable - gives you a very handy solution for translating records into different languages. Easy to setup, easier to use.
- Sluggable - urlizes your specified fields into single unique slug
- Timestampable - updates date fields on create, update and even property change.
- Loggable - helps tracking changes and history of objects, also supports version management.

Currently these extensions support **Yaml** and **Annotation** mapping. Additional mapping drivers
can be easy implemented using Mapping extension to handle the additional metadata mapping.

**Notice:** from now on there is only one listener per extension which supports ODM
and ORM adapters to deal with objects. Only one instance of listener is required, and can be attached
to many different type object managers, currently supported (ORM or ODM) 

Notice xml: Please note, that xml mapping needs to be in a different namespace, the declared namespace for
Doctrine extensions is http://gediminasm.org/schemas/orm/doctrine-extensions-mapping
So root node now looks like this:
```
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                 xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
...
</doctrine-mapping>

### Latest updates

**2011-06-14**

- Add support for materialized paths which is currently only supported in MongoDB. However support is finally available for running
a tree structure in MongoDB with Doctrine!

**2011-06-08**

- [mvrhov](http://github.com/mvrhov) implemented the XML driver for extensions and now
there is a full stack of drivers to make your experience even better using these extensions.
So far I'm not sure if the same xsd will work with ODM but it will be created in comming month.

**2011-05-23**

- Recently **doctrine-common** library changed the way for annotation mapping in branch **3.0.x**
If you are a **Symfony2** user, you will notice that shortly. Extensions were upgraded to support
injection of annotation reader into listener which makes them compatible with these changes. For more
details look in **doc/annotations.md**

**2011-05-07**

- Tree **closure** strategy was refactored and now fully functional. Actually nested-set
is performing faster during concurrent inserts and moving subtrees and it also supports
ordering of nodes.
- Also there are good news for ODM users, @mtotheikle is working on **materialized path**
strategy for ODM Tree like documents.

**2011-04-16**

- Translation **query walker** is a killer feature for translatable extension. It lets to
translate any query components and filter or order by translated fields. I recommend you
to use it extensively since it is very performative also.

### ODM MongoDB support

Extensions which fully supports ODM:

- Translatable
- Sluggable
- Timestampable
- Loggable

All these extensions can be nested together. And most already use only annotations without interface requirement
to not to aggregate the entity itself and has implemented proper caching for metadata.

**Notice:** extension tutorial on doctrine blog is outdated.
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
- run: **git submodule update --init**
- run: **phpunit -c tests**
- optional - run mongodb in background to complete all tests

### Contributors:

- Miha Vrhovnik [mvrhov](http://github.com/mvrhov)
- Clément JOBEILI [dator](http://github.com/dator)
- Illya Klymov [xanf](http://github.com/xanf)
- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Christophe Coevoet [stof](http://github.com/stof)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- Klein Florian [docteurklein](http://github.com/docteurklein)
- Michael Williams [mtotheikle](http://github.com/mtotheikle)
