<?php

namespace Gedmo\Translatable;

use Doctrine\Common\Util\Debug,
    Translatable\Fixture\StringIdentifier;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableIdentifierTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Translatable\Fixture\StringIdentifier';
    const TRANSLATION_CLASS = 'Gedmo\Translatable\Entity\Translation';
    private $testObjectId;
    private $translationListener;
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Translatable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translationListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TRANSLATION_CLASS),
        ));
    }
    
    public function testStringIdentifier()
    {
        $object = new StringIdentifier();
        $object->setTitle('title in en');
        $object->setUid(md5(self::TEST_ENTITY_CLASS . time()));

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();
        $this->testObjectId = $object->getUid();
        
        $repo = $this->em->getRepository(self::TRANSLATION_CLASS);
        $object = $this->em->find(self::TEST_ENTITY_CLASS, $this->testObjectId);
        
        $translations = $repo->findTranslations($object);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('title in en', $translations['en_us']['title']);

        $object = $this->em->find(self::TEST_ENTITY_CLASS, $this->testObjectId);
        $object->setTitle('title in de');
        $object->setTranslatableLocale('de_de');

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TRANSLATION_CLASS);
        
        // test the entity load by translated title
        $object = $repo->findEntityByTranslatedField(
            'title',
            'title in en',
            self::TEST_ENTITY_CLASS
        );
        
        $this->assertEquals($this->testObjectId, $object->getUid());
        
        $object = $repo->findEntityByTranslatedField(
            'title',
            'title in de',
            self::TEST_ENTITY_CLASS
        );
        
        $this->assertEquals($this->testObjectId, $object->getUid());
        
        $translations = $repo->findTranslations($object);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        // dql test object hydration
        $q = $this->em->createQuery('SELECT si FROM ' . self::TEST_ENTITY_CLASS . ' si WHERE si.uid = :id');
        $data = $q->execute(
            array('id' => $this->testObjectId),
            \Doctrine\ORM\Query::HYDRATE_OBJECT
        );
        $this->assertEquals(count($data), 1);
        $object = $data[0];
        $this->assertEquals('title in en', $object->getTitle());
        
        $this->translationListener->setTranslatableLocale('de_de');
        $q = $this->em->createQuery('SELECT si FROM ' . self::TEST_ENTITY_CLASS . ' si WHERE si.uid = :id');
        $data = $q->execute(
            array('id' => $this->testObjectId),
            \Doctrine\ORM\Query::HYDRATE_OBJECT
        );
        $this->assertEquals(count($data), 1);
        $object = $data[0];
        $this->assertEquals('title in de', $object->getTitle());
    }
}
