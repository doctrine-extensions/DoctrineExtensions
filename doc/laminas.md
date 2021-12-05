## Using Gedmo Doctrine Extensions in Laminas

Assuming you are familiar with [DoctrineModule](https://github.com/doctrine/DoctrineModule) (if not, you should definitely start there!), integrating Doctrine Extensions with Laminas application is super-easy.

### Composer

Add `doctrine/doctrine-module`, `doctrine/doctrine-orm-module` or `doctrine/doctrine-mongo-odm-module` to composer.json file

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

That's it! From now on you can use Gedmo annotations, just as it is described in [documentation](./annotations.md).

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
                    'vendor/gedmo/doctrine-extensions/src/Translatable/Entity',
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
