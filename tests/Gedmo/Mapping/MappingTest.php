<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Util\Debug,
    Tree\Fixture\BehavioralCategory,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappingTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CATEGORY = "Tree\Fixture\BehavioralCategory";
    const TEST_ENTITY_TRANSLATION = "Gedmo\Translatable\Entity\Translation";

    private $em;
    private $timestampable;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        //$this->markTestSkipped('Skipping according to a bug in annotation reader creation.');
        $config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($_ENV['annotation_reader']));

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new \Gedmo\Translatable\TranslationListener());
        $this->timestampable = new \Gedmo\Timestampable\TimestampableListener();
        $evm->addEventSubscriber($this->timestampable);
        $evm->addEventSubscriber(new \Gedmo\Sluggable\SluggableListener());
        $evm->addEventSubscriber(new \Gedmo\Tree\TreeListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION)
        ));
    }

    public function testNoCacheImplementationMapping()
    {
        $food = new BehavioralCategory();
        $food->setTitle('Food');
        $this->em->persist($food);
        $this->em->flush();
        // assertion checks if configuration is read correctly without cache driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            self::TEST_ENTITY_CATEGORY
        );
        $this->assertEquals(0, count($conf));
    }
}
