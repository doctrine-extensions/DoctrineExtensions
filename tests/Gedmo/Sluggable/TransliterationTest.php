<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Util\Debug,
    Sluggable\Fixture\Article;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TransliterationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Sluggable\Fixture\Article';
    
    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Sluggable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $sluggableListener = new SluggableListener();
        $evm->addEventSubscriber($sluggableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS)
        ));
        
        $this->populate();
    }
    
    public function testInsertedNewSlug()
    {
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        
        $lithuanian = $repo->findOneByCode('lt');
        $this->assertEquals('transliteration-test-usage-uz-lt', $lithuanian->getSlug());
        
        $bulgarian = $repo->findOneByCode('bg');
        $this->assertEquals('tova-ie-tiestovo-zaghlaviie-bg', $bulgarian->getSlug());
        
        $russian = $repo->findOneByCode('ru');
        $this->assertEquals('eto-tiestovyi-zagholovok-ru', $russian->getSlug());
        
        $german = $repo->findOneByCode('de');
        $this->assertEquals('fuhren-aktivitaten-haglofs-de', $german->getSlug());
    }
    
    private function populate()
    {
        $lithuanian = new Article;
        $lithuanian->setTitle('trąnslįteration tėst ųsąge ūž');
        $lithuanian->setCode('lt');
        
        $bulgarian = new Article;
        $bulgarian->setTitle('това е тестово заглавие');
        $bulgarian->setCode('bg');
        
        $russian = new Article;
        $russian->setTitle('это тестовый заголовок');
        $russian->setCode('ru');
        
        $german = new Article;
        $german->setTitle('führen Aktivitäten Haglöfs');
        $german->setCode('de');
        
        $this->em->persist($lithuanian);
        $this->em->persist($bulgarian);
        $this->em->persist($russian);
        $this->em->persist($german);
        $this->em->flush();
        $this->em->clear();
    }
}