<?php

namespace Gedmo\Translatable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\TestTool\ObjectManagerTestCase;

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
        $meta = $this->em->getClassMetadata('Gedmo\Fixture\Translatable\Post');
        $exm = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertFalse($exm->isEmpty());
        $this->assertCount(2, $fields = $exm->getFields());

        $this->assertContains('title', $fields);
        $this->assertContains('content', $fields);

        $this->assertSame('Gedmo\Fixture\Translatable\PostTranslation', $exm->getTranslationClass());

    }
}

