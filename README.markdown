# Some Doctrine 2 Extensions

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficently. This package contains mostly
used behaviors which can be easily attached to your event system of Doctrine 2 and handle the
records being flushed in the behavioral way. List of extensions:

- Tree - this extension automates the tree handling process and adds some tree specific functions on repository. (closure or nestedset)
- Translatable - gives you a very handy solution for translating records into diferent languages. Easy to setup, easier to use.
- Sluggable - urlizes your specified fields into single unique slug
- Timestampable - updates date fields on create, update and even property change.
- Loggable - helps tracking changes and history of objects, also supports version managment.

Currently these extensions support **Yaml** and **Annotation** mapping. Additional mapping drivers
can be easy implemented using Mapping extension to handle the additional metadata mapping.

Notice: from now on there is only one listener per extension which supports ODM and ORM adapters to deal with objects. Only one instance of listener is 
required, and can be attached to many different type object managers, currently supported (ORM or ODM)

## Important

Recently where was a change for type hinting on object manager and other. These changes
**requires doctrine2 from master branch**. If you do not want to update your doctrine libraries
use these extensions from separate branch **doctrine2.0.x** or simply checkout to this branch.

### Latest updates

**2011-04-04**

- Extensions now use only one listener instance for different object managers

### ODM MongoDB support

List of extensions which support ODM

- Translatable
- Sluggable
- Timestampable
- Loggable

All these extensions can be nested together. And most allready use only annotations without interface requirement
to not to aggregate the entity itself and has implemented proper caching for metadata.

There is a post introducing to these extensions on [doctrine project](http://www.doctrine-project.org/blog/doctrine2-behavioral-extensions "Doctrine2 behavior extensions")

You can test these extensions on [my blog](http://gediminasm.org/test/ "Test doctrine behavior extensions").

All tutorials for basic usage examples are on [my blog](http://gediminasm.org "Tutorials for extensions") also.

### Running the tests:

PHPUnit 3.4 or newer is required.
To setup and run tests follow these steps:

- go to the root directory of extensions
- run: **git submodule init**
- run: **git submodule update**
- go to tests directory: **cd tests**
- run **cp phpunit.dist.xml phpunit.xml**
- run: **phpunit**
- optional - run mongodb in background to complete all tests 

### Contributors:

- Gustavo Adrian [comfortablynumb](http://github.com/comfortablynumb)
- Boussekeyt Jules [gordonslondon](http://github.com/gordonslondon)
- Christophe Coevoet [stof](http://github.com/stof)
- Kudryashov Konstantin [everzet](http://github.com/everzet)
- Klein Florian [docteurklein](http://github.com/docteurklein)
