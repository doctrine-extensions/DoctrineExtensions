<?php

namespace Gedmo\Translatable;

use Doctrine\Common\Util\Debug,
    Translatable\Fixture\PersonTranslation,
    Translatable\Fixture\Person;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityTranslationTableTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Translatable\Fixture\Person';
    const TEST_ENTITY_TRANSLATION = 'Translatable\Fixture\PersonTranslation';
    
    private $translatableListener;
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
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION)
        ));
    }
    
    public function testFixtureGeneratedTranslations()
    {
        $person = new Person;
        $person->setName('name in en');
        
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TEST_ENTITY_TRANSLATION);
        $this->assertTrue($repo instanceof Repository\TranslationRepository);
        
        $translations = $repo->findTranslations($person);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        
        $this->assertArrayHasKey('name', $translations['en_us']);
        $this->assertEquals('name in en', $translations['en_us']['name']);
        // test second translations
        $person = $this->em->find(self::TEST_ENTITY_CLASS, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');
        
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();
        
        $translations = $repo->findTranslations($person);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);
        
        $this->translatableListener->setTranslatableLocale('en_us');
    }
}
