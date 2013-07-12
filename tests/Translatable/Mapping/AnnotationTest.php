<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Gedmo\Translatable\TranslatableListener;

class AnnotationTest extends ObjectManagerTestCase
{
    private $translatable;
    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $this->em = $this->createEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldMapTranslatableEntity()
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

