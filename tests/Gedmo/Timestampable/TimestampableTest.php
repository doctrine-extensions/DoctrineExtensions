<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\Util\Debug,
    Timestampable\Fixture\Article,
    Timestampable\Fixture\Comment,
    Timestampable\Fixture\Type;

/**
 * These are tests for Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_ARTICLE = "Timestampable\Fixture\Article";
    const TEST_ENTITY_COMMENT = "Timestampable\Fixture\Comment";
    const TEST_ENTITY_TYPE = "Timestampable\Fixture\Type";
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
        $timestampableListener = new TimestampableListener();
        $evm->addEventSubscriber($timestampableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_COMMENT),
            $this->em->getClassMetadata(self::TEST_ENTITY_TYPE)
        ));
    }
    
    public function testTimestampable()
    {        
        $sport = new Article();
        $sport->setTitle('Sport');
        
        $this->assertTrue($sport instanceof Timestampable);
        
        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);
        
        $this->assertTrue($sportComment instanceof Timestampable);
        
        $date = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();
        
        $sport = $this->em->getRepository(self::TEST_ENTITY_ARTICLE)->findOneByTitle('Sport');
        $this->assertEquals(
            $date->format('Y-m-d 00:00:00'), 
            $sport->getCreated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(null, $sport->getPublished());
        
        $sportComment = $this->em->getRepository(self::TEST_ENTITY_COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $date->format('H:i:s'), 
            $sportComment->getModified()->format('H:i:s')
        );
        $this->assertEquals(null, $sportComment->getClosed());
        
        sleep(1);
        
        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');
        
        $sport->setTitle('Updated');
        $sport->setType($published);
        $date = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();
        
        $sportComment = $this->em->getRepository(self::TEST_ENTITY_COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $sportComment->getClosed()->format('Y-m-d H:i:s')
        );
        
        $sport = $this->em->getRepository(self::TEST_ENTITY_ARTICLE)->findOneByTitle('Updated');
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
    }
}
