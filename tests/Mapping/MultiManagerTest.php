<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Tool\BaseTestCaseOM;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;

class MultiManagerTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em1;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em2;

    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm1;

    public function setUp()
    {
        parent::setUp();
        // EM with standard annotation mapping
        $this->em1 = $this->getMockSqliteEntityManager(array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation'
        ));
        // EM with yaml mapping
        $yamlDriver = new YamlDriver(__DIR__.'/../Translatable/Mapping');
        $yamlSimplifiedDriver = new SimplifiedYamlDriver(array(
            __DIR__.'/../../lib/Gedmo/Translatable/Mapping/Resources' => 'Gedmo\Translatable\Entity\MappedSuperclass'
        ), '.orm.yml');

        $chain = new DriverChain;
        $chain->addDriver($yamlSimplifiedDriver, 'Gedmo\Translatable');
        $chain->addDriver($yamlDriver, 'Fixture\Unmapped');

        $this->em2 = $this->getMockSqliteEntityManager(array(
            'Fixture\Unmapped\Translatable',
            'Fixture\Unmapped\TranslatableTranslation',
        ), $chain);
        // DM with standard annotation mapping
        $this->dm1 = $this->getMockDocumentManager('gedmo_extensions_test');
    }

    /**
     * @test
     */
    function shouldHandleThreeObjectManagerInteraction()
    {
        $dmPost = new \Fixture\Translatable\Document\Post;
        $dmPost->setTitle('hello');
        $this->dm1->persist($dmPost);
        $this->dm1->flush();

        $this->assertCount(1, $dmPost->getTranslations());

        $em1Post = new \Fixture\Translatable\Post;
        $em1Post->setTitle('hello world');
        $this->em1->persist($em1Post);
        $this->em1->flush();

        $this->assertCount(1, $em1Post->getTranslations());

        $em2Translatable = new \Fixture\Unmapped\Translatable;
        $em2Translatable->setTitle('Hi');
        $em2Translatable->setContent('World');
        $em2Translatable->setAuthor('gedi');
        $this->em2->persist($em2Translatable);
        $this->em2->flush();

        $this->assertCount(1, $this->em2->getRepository('Fixture\Unmapped\TranslatableTranslation')->findAll());
    }
}
