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
        $exm = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertFalse($exm->isEmpty());
        $this->assertCount(2, $fields = $exm->getFields());

        $this->assertContains('title', $fields);
        $this->assertContains('content', $fields);

        $this->assertSame('Fixture\Translatable\PostTranslation', $exm->getTranslationClass());
    }
}
