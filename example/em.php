<?php
// connection args, modify at will
$connection = array(
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'password' => 'nimda',
    'dbname' => 'test',
    'driver' => 'pdo_mysql'
);

// First of all autoloading of vendors

$vendorPath = realpath(__DIR__.'/../vendor');
$gedmoPath = realpath(__DIR__.'/../lib');

$doctrineClassLoaderFile = $vendorPath.'/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
if (!file_exists($doctrineClassLoaderFile)) {
    die('cannot find vendor, run: php bin/vendors.php to install doctrine');
}

require $doctrineClassLoaderFile;
use Doctrine\Common\ClassLoader;
// autoload all vendors

$loader = new ClassLoader('Doctrine\Common', $vendorPath.'/doctrine-common/lib');
$loader->register();

$loader = new ClassLoader('Doctrine\DBAL', $vendorPath.'/doctrine-dbal/lib');
$loader->register();

$loader = new ClassLoader('Doctrine\ORM', $vendorPath.'/doctrine-orm/lib');
$loader->register();

// gedmo extensions
$loader = new ClassLoader('Gedmo', $gedmoPath);
$loader->register();

// if you use yaml, you need a yaml parser, same as command line tool
$loader = new ClassLoader('Symfony', $vendorPath);
$loader->register();

// autoloader for Entity namespace
$loader = new ClassLoader('Entity', __DIR__.'/app');
$loader->register();
// Second configure ORM

$config = new Doctrine\ORM\Configuration;
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false);

// standard annotation reader
$annotationReader = new Doctrine\Common\Annotations\AnnotationReader;
// gedmo annotation loader
Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'Gedmo\Mapping\Annotation',
    $gedmoPath
);
// standard doctrine annotations
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    $vendorPath.'/doctrine-orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);
// register annotation driver
$driverChain = new Doctrine\ORM\Mapping\Driver\DriverChain();
$annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver($annotationReader, array(
    __DIR__.'/app/Entity', // example entity
    $gedmoPath.'/Gedmo/Translatable/Entity',
    $gedmoPath.'/Gedmo/Loggable/Entity',
    $gedmoPath.'/Gedmo/Tree/Entity',
));

// drivers for diferent namespaces
$driverChain->addDriver($annotationDriver, 'Entity');
$driverChain->addDriver($annotationDriver, 'Gedmo\Translatable\Entity');
$driverChain->addDriver($annotationDriver, 'Gedmo\Loggable\Entity');
$driverChain->addDriver($annotationDriver, 'Gedmo\Tree\Entity');

// register metadata driver
$config->setMetadataDriverImpl($driverChain);
// cache
$config->setMetadataCacheImpl(new Doctrine\Common\Cache\ArrayCache);
$config->setQueryCacheImpl(new Doctrine\Common\Cache\ArrayCache);

$evm = new Doctrine\Common\EventManager();
// gedmo extension listeners
$evm->addEventSubscriber(new Gedmo\Sluggable\SluggableListener);
$evm->addEventSubscriber(new Gedmo\Tree\TreeListener);
$evm->addEventSubscriber(new Gedmo\Loggable\LoggableListener);
$evm->addEventSubscriber(new Gedmo\Timestampable\TimestampableListener);
$translatable = new Gedmo\Translatable\TranslationListener;
$translatable->setTranslatableLocale('en');
$translatable->setDefaultLocale('en');
$evm->addEventSubscriber($translatable);

// mysql set names UTF-8
$evm->addEventSubscriber(new Doctrine\DBAL\Event\Listeners\MysqlSessionInit());
// create entity manager
return Doctrine\ORM\EntityManager::create($connection, $config, $evm);
