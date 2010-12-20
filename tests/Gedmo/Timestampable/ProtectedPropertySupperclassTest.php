<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ProtectedPropertySupperclassTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Timestampable\Fixture\SupperClassExtension";
    const TEST_ENTITY_TRANSLATION = "Gedmo\Translatable\Entity\Translation";
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Timestampable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());
        $translationListener = new \Gedmo\Translatable\TranslationListener();
        $translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($translationListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION)
        ));
    }
    
    public function testProtectedProperty()
    {
        $test = new \Timestampable\Fixture\SupperClassExtension;
        $test->setName('name');
        $test->setTitle('title');
        
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TEST_ENTITY_TRANSLATION);
        $translations = $repo->findTranslations($test);
        $this->assertEquals(1, count($translations));
        $this->assertArrayHasKey('en_us', $translations);
        $this->assertEquals(2, count($translations['en_us']));
        
        $this->assertTrue($test->getCreatedAt() !== null);
    }
}
