<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\Translatable\Post;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\TestTool\ObjectManagerTestCase;

class SoftdeletedTranslationTest extends ObjectManagerTestCase
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
        $evm->addEventSubscriber(new SoftDeleteableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            'Gedmo\Fixture\Translatable\Post',
            'Gedmo\Fixture\Translatable\PostTranslation',
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
    public function shouldNotRemoveTranslationsWhenSoftdeleted()
    {
        $repo = $this->em->getRepository('Gedmo\Fixture\Translatable\PostTranslation');

        $post = new Post();
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

        $post = $this->em->getRepository('Gedmo\Fixture\Translatable\Post')->findOneById($id);
        $this->assertNull($post, "Post should have been softdeleted");

        $translations = $repo->findAll();
        $this->assertSame(2, count($translations), "Number of translations should remain");

        // now disable filter and remove
        $this->em->getFilters()->disable('soft-deleteable');
        $post = $this->em->getRepository('Gedmo\Fixture\Translatable\Post')->findOneById($id);
        $this->assertNotNull($post);

        $this->em->remove($post);
        $this->em->flush();

        $translations = $repo->findAll();
        $this->assertSame(0, count($translations), "Translations should be removed");
    }
}
