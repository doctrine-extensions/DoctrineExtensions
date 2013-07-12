<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Translatable\TranslatableListener;
use TestTool\ObjectManagerTestCase;

class AnnotationTest extends ObjectManagerTestCase
{
    /**
     * @var TranslatableListener
     */
    private $translatable;
    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber($this->translatable = new TranslatableListener());
        $this->em = $this->createEntityManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldMapTranslatableEntity()
    {
        $meta = $this->em->getClassMetadata('Fixture\Translatable\Post');
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertTrue(!empty($config));
        // translation class
        $this->assertArrayHasKey('translationClass', $config);
        $this->assertEquals('Fixture\Translatable\PostTranslation', $config['translationClass']);
        // translatable fields
        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(2, $config['fields']);
        $this->assertArrayHasKey('title', $config['fields']);
        $this->assertArrayHasKey('content', $config['fields']);
    }
}
