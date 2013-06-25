<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Translatable\TranslatableListener;

class AnnotationTest extends BaseTestCaseORM
{
    private $translatable;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);

        $this->getMockSqliteEntityManager($evm);
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

    protected function getUsedEntityFixtures()
    {
        return array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation',
        );
    }
}

