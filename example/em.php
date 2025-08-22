<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Blameable\BlameableListener;
use Gedmo\DoctrineExtensions;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

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

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

// Register the example app with the autoloader
$loader->addPsr4('App\\', __DIR__.'/app');

// Define our global cache backend for the application.
// For larger applications, you may use multiple cache pools to store cacheable data in different locations.
$cache = new ArrayAdapter();

$annotationReader = null;
$extensionReader = null;

// For PHP 8, we will provide the extensions an attribute reader, while PHP 7 will require the annotation reader
// (which will only be created when `doctrine/annotations` is installed)
if (PHP_VERSION_ID >= 80000) {
    $extensionReader = new AttributeReader();
}

// Build the annotation reader for the application when the `doctrine/annotations` package is installed.
// By default, we will use a decorated reader supporting a backend cache.
if (class_exists(AnnotationReader::class)) {
    $extensionReader = $annotationReader = new PsrCachedReader(
        new AnnotationReader(),
        $cache
    );
}

// Create the mapping driver chain that will be used to read metadata from our various sources.
$mappingDriver = new MappingDriverChain();

// Load the superclass metadata mapping for the extensions into the driver chain.
// Internally, this will also register the Doctrine Extensions annotations.
DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
    $mappingDriver,
    $annotationReader
);

// Register the application entities to our driver chain.
// Our application uses Annotations or Attributes for mapping, but you can also use XML.
if (PHP_VERSION_ID >= 80000) {
    $mappingDriver->addDriver(
        new AttributeDriver(
            [__DIR__.'/app/Entity']
        ),
        'App\Entity'
    );
} else {
    $mappingDriver->addDriver(
        new AnnotationDriver(
            $annotationReader,
            [__DIR__.'/app/Entity']
        ),
        'App\Entity'
    );
}

// Next, we will create the event manager and register the listeners for the extensions we will be using.
$eventManager = new EventManager();

// Sluggable extension
$sluggableListener = new SluggableListener();
$sluggableListener->setAnnotationReader($extensionReader);
$sluggableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($sluggableListener);

// Tree extension
$treeListener = new TreeListener();
$treeListener->setAnnotationReader($extensionReader);
$treeListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($treeListener);

// Loggable extension, not used in example
// $loggableListener = new Gedmo\Loggable\LoggableListener;
// $loggableListener->setAnnotationReader($extensionReader);
// $loggableListener->setCacheItemPool($cache);
// $loggableListener->setUsername('admin');
// $eventManager->addEventSubscriber($loggableListener);

// Timestampable extension
$timestampableListener = new TimestampableListener();
$timestampableListener->setAnnotationReader($extensionReader);
$timestampableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($timestampableListener);

// Blameable extension
$blameableListener = new BlameableListener();
$blameableListener->setAnnotationReader($extensionReader);
$blameableListener->setCacheItemPool($cache);
$blameableListener->setUserValue('MyUsername'); // determine from your environment
$eventManager->addEventSubscriber($blameableListener);

// Translatable
$translatableListener = new TranslatableListener();

// The current translation locale should be set from session or some other request data,
// but most importantly, it must be set before the entity manager is flushed.
$translatableListener->setTranslatableLocale('en');
$translatableListener->setDefaultLocale('en');
$translatableListener->setAnnotationReader($extensionReader);
$translatableListener->setCacheItemPool($cache);
$eventManager->addEventSubscriber($translatableListener);

// Sortable extension, not used in example
// $sortableListener = new Gedmo\Sortable\SortableListener;
// $sortableListener->setAnnotationReader($extensionReader);
// $sortableListener->setCacheItemPool($cache);
// $eventManager->addEventSubscriber($sortableListener);

// Now we will build our ORM configuration.
$config = new Configuration();
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false);
$config->setMetadataDriverImpl($mappingDriver);
$config->setMetadataCache($cache);
$config->setQueryCache($cache);
$config->setResultCache($cache);

$connection = DriverManager::getConnection($connection, $config);

// Finally, we create and return the entity manager

return new EntityManager($connection, $config, $eventManager);
