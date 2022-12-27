<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// this entity manager configuration works with the Doctrine DBAL and ORM.
// Regarding the AnnotationDriver setup, it most probably will be changed into
// XML because the annotation driver fails to read other classes in same namespace.

// Database connection configuration, modify at will
// Below is an example MySQL configuration
$connection = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'password' => null,
    'dbname' => 'doctrine_extensions_example',
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
];

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo 'Composer has not been properly set up, please read the README.md file for setup instructions.';

    exit(1);
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

// Register the example app with the autoloader
$loader->addPsr4('App\\', __DIR__.'/app');

// Define our global cache backend for the application.
// For larger applications, you may use multiple cache pools to store cacheable data in different locations.
$cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();

// Build the annotation reader for the application,
// by default we will use a decorated reader supporting a backend cache.
$annotationReader = new \Doctrine\Common\Annotations\PsrCachedReader(
    new \Doctrine\Common\Annotations\AnnotationReader(),
    $cache
);

// Create the mapping driver chain that will be used to read metadata from our various sources.
$mappingDriver = new \Doctrine\Persistence\Mapping\Driver\MappingDriverChain();

// Load the superclass metadata mapping for the extensions into the driver chain.
// Internally, this will also register the Doctrine Extensions annotations.
Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
    $mappingDriver,
    $annotationReader
);

// Register the application entities to our driver chain.
// Our application uses Annotations for mapping, but you can also use XML.
$mappingDriver->addDriver(
    new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
        $annotationReader,
        [__DIR__.'/app/Entity']
    ),
    'App\Entity'
);

// Next, we will create the event manager and register the listeners for the extensions we will be using.
$eventManager = new Doctrine\Common\EventManager();

// Sluggable extension
$sluggableListener = new Gedmo\Sluggable\SluggableListener();
$sluggableListener->setAnnotationReader($annotationReader);
$sluggableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($sluggableListener);

// Tree extension
$treeListener = new Gedmo\Tree\TreeListener();
$treeListener->setAnnotationReader($annotationReader);
$treeListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($treeListener);

// Loggable extension, not used in example
// $loggableListener = new Gedmo\Loggable\LoggableListener;
// $loggableListener->setAnnotationReader($annotationReader);
// $loggableListener->setCacheItemPool($cache);
// $loggableListener->setUsername('admin');
// $eventManager->addEventSubscriber($loggableListener);

// Timestampable extension
$timestampableListener = new Gedmo\Timestampable\TimestampableListener();
$timestampableListener->setAnnotationReader($annotationReader);
$timestampableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($timestampableListener);

// Blameable extension
$blameableListener = new \Gedmo\Blameable\BlameableListener();
$blameableListener->setAnnotationReader($annotationReader);
$blameableListener->setCacheItemPool($cache);
$blameableListener->setUserValue('MyUsername'); // determine from your environment
$eventManager->addEventSubscriber($blameableListener);

// Translatable
$translatableListener = new Gedmo\Translatable\TranslatableListener();

// The current translation locale should be set from session or some other request data,
// but most importantly, it must be set before the entity manager is flushed.
$translatableListener->setTranslatableLocale('en');
$translatableListener->setDefaultLocale('en');
$translatableListener->setAnnotationReader($annotationReader);
$translatableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($translatableListener);

// Sortable extension, not used in example
// $sortableListener = new Gedmo\Sortable\SortableListener;
// $sortableListener->setAnnotationReader($annotationReader);
// $sortableListener->setCacheItemPool($cache);
// $eventManager->addEventSubscriber($sortableListener);

// Now we will build our ORM configuration.
$config = new Doctrine\ORM\Configuration();
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false);
$config->setMetadataDriverImpl($mappingDriver);
$config->setMetadataCache($cache);
$config->setQueryCache($cache);
$config->setResultCache($cache);

// Finally, we create the entity manager
return Doctrine\ORM\EntityManager::create($connection, $config, $eventManager);
