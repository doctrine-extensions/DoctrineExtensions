<?php

namespace Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseORM;
use Fixture\Translatable\Post;
use Fixture\Translatable\PostTranslation;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class SoftdeletedTranslationTest extends BaseTestCaseORM
{
    private $translatable;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('soft-deleteable');

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

    protected function getUsedEntityFixtures()
    {
        return array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation',
        );
    }
}
