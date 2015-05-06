## Using Gedmo Doctrine Extensions in Zend Framework 2

Assuming you are familiar with [DoctrineModule](https://github.com/doctrine/DoctrineModule) (if not, you should definitely start there!), integrating Doctrine Extensions with Zend Framework 2 application is super-easy.

### Composer

Add DoctrineModule, DoctrineORMModule and DoctrineExtensions to composer.json file:

```json
{
    "require": {
        "php": ">=5.3.3",
        "zendframework/zendframework": "2.1.*",
        "doctrine/doctrine-module": "0.*",
        "doctrine/doctrine-orm-module": "0.*",
        "gedmo/doctrine-extensions": "2.3.*",
    }
}
```

Then run `composer.phar update`.

### Configuration

Once libraries are installed, you can tell Doctrine which behaviors you want to use, by declaring appropriate subscribers in Event Manager settings. Together with [entity mapping options](https://github.com/doctrine/DoctrineORMModule#entities-settings), your module configuration file should look like following:

```php
return array(
    'doctrine' => array(
        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(

                    // pick any listeners you need
                    'Gedmo\Tree\TreeListener',
                    'Gedmo\Timestampable\TimestampableListener',
                    'Gedmo\Sluggable\SluggableListener',
                    'Gedmo\Loggable\LoggableListener',
                    'Gedmo\Sortable\SortableListener'
                ),
            ),
        ),
        'driver' => array(
            'my_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/MyModule/Entity')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'MyModule\Entity' => 'my_driver'
                ),
            ),
        ),
    ),
);
```

That's it! From now on you can use Gedmo annotations, just as it is described in [documentation](https://github.com/mtymek/DoctrineExtensions/blob/master/doc/annotations.md).

#### Note: You may need to provide additional settings for some of the available listeners.

For instance, `Translatable` requires additional metadata driver in order to manage translation tables:

```php
return array(
    'doctrine' => array(
        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(
                    'Gedmo\Translatable\TranslatableListener',
                ),
            ),
        ),
        'driver' => array(
            'my_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/MyModule/Entity')
            ),
            'translatable_metadata_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(
                    'vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity',
                ),
            ),
            'orm_default' => array(
                'drivers' => array(
                    'MyModule\Entity' => 'my_driver',
                    'Gedmo\Translatable\Entity' => 'translatable_metadata_driver',
                ),
            ),
        ),
    ),
);
```
