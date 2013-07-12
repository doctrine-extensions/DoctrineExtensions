<?php

namespace Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use TestTool\ObjectManagerTestCase;
use Fixture\Translatable\Post;
use Fixture\Translatable\PostTranslation;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class SoftdeletedTranslationTest extends ObjectManagerTestCase
{
    private $translatable;
    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation',
        ));
        // hook softdeleteable filter
        $this->em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('soft-deleteable');
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldNotRemoveTranslationsWhenSoftdeleted()
    {
        $repo = $this->em->getRepository('Fixture\Translatable\PostTranslation');

        $post = new Post;
        $post->setTitle('title en');
        $this->em->persist($post);
        $this->em->flush();

        $id = $post->getId();

        $this->translatable->setTranslatableLocale('de');
        $post->setTitle('title de');
        $this->em->persist($post);
        $this->em->flush();

        $translations = $repo->findAll();
        $this->assertSame(2, count($translations));

        $this->em->remove($post);
        $this->em->flush();

        $post = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($id);
        $this->assertNull($post, "Post should have been softdeleted");

        $translations = $repo->findAll();
        $this->assertSame(2, count($translations), "Number of translations should remain");

        // now disable filter and remove
        $this->em->getFilters()->disable('soft-deleteable');
        $post = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($id);
        $this->assertNotNull($post);

        $this->em->remove($post);
        $this->em->flush();

        $translations = $repo->findAll();
        $this->assertSame(0, count($translations), "Translations should be removed");
    }
}
