<?php

namespace Gedmo\Mapping;

use Mapping\Fixture\Compatibility\Article;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Cache\ArrayCache;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CompatibilityMappingTest extends \PHPUnit_Framework_TestCase
{
    const ARTICLE = "Mapping\Fixture\Compatibility\Article";

    private $em;
    private $timestampable;

    public function setUp()
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.1.0RC4-DEV', '>=')) {
            $this->markTestSkipped('Doctrine common is 2.1.0RC4-DEV version, skipping.');
        } else if (version_compare(\Doctrine\Common\Version::VERSION, '2.1.0-BETA3-DEV', '>=')) {
            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            $reader->setIgnoreNotImportedAnnotations(true);
            $reader->setAnnotationNamespaceAlias('Gedmo\\Mapping\\Annotation\\', 'gedmo');
            $reader->setEnableParsePhpImports(false);
            $reader->setAutoloadAnnotations(true);
            $reader = new CachedReader(
                new \Doctrine\Common\Annotations\IndexedReader($reader), new ArrayCache()
            );
        } else {
            $reader = new AnnotationReader();
            $reader->setAutoloadAnnotations(true);
            $reader->setAnnotationNamespaceAlias('Gedmo\\Mapping\\Annotation\\', 'gedmo');
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader));

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $this->timestampable = new \Gedmo\Timestampable\TimestampableListener();
        $evm->addEventSubscriber($this->timestampable);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::ARTICLE)
        ));
    }

    public function testNoCacheImplementationMapping()
    {
        $test = new Article;
        $test->setTitle('test');
        $this->em->persist($test);
        $this->em->flush();
        // assertion checks if configuration is read correctly without cache driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            self::ARTICLE
        );
        $this->assertArrayHasKey('create', $conf);
        $this->assertArrayHasKey('update', $conf);

    }
}
