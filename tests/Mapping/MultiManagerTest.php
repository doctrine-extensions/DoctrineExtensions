<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use TestTool\ObjectManagerTestCase;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;

class MultiManagerTest extends ObjectManagerTestCase
{
    private $em1;
    private $em2;
    private $dm1;

    public function setUp()
    {
        // EM with standard annotation mapping
        $this->em1 = $this->createEntityManager();
        $this->createSchema($this->em1, array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation'
        ));

        // EM with yaml mapping
        $yamlDriver = new YamlDriver(__DIR__.'/../Translatable/Mapping');
        $yamlSimplifiedDriver = new SimplifiedYamlDriver(array(
            $this->getRootDir().'/lib/Gedmo/Translatable/Mapping/Resources' => 'Gedmo\Translatable\Entity\MappedSuperclass'
        ), '.orm.yml');

        $chain = new DriverChain;
        $chain->addDriver($yamlSimplifiedDriver, 'Gedmo\Translatable');
        $chain->addDriver($yamlDriver, 'Fixture\Unmapped');

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl($chain);

        $conn = $this->getDefaultDbalConnectionParams();
        if (isset($conn['dbname'])) {
            $conn['dbname'] .= '2'; // should have a different database name
        }
        $this->em2 = $this->createEntityManager(null, $conn, $config);
        $this->createSchema($this->em2, array(
            'Fixture\Unmapped\Translatable',
            'Fixture\Unmapped\TranslatableTranslation',
        ));
        // DM with standard annotation mapping
        $this->dm1 = $this->createDocumentManager();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em1);
        $this->releaseEntityManager($this->em2);
        $this->releaseDocumentManager($this->dm1);
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
