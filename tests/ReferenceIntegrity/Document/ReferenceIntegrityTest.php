<?php

namespace ReferenceIntegrity\Document;

use Doctrine\Common\EventManager;
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for the ReferenceIntegrity extension
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ReferenceIntegrityTest extends ObjectManagerTestCase
{
    const TYPE_ONE_NULLIFY_CLASS = 'Fixture\ReferenceIntegrity\Document\OneNullify\Type';
    const ARTICLE_ONE_NULLIFY_CLASS = 'Fixture\ReferenceIntegrity\Document\OneNullify\Article';

    const TYPE_MANY_NULLIFY_CLASS = 'Fixture\ReferenceIntegrity\Document\ManyNullify\Type';
    const ARTICLE_MANY_NULLIFY_CLASS = 'Fixture\ReferenceIntegrity\Document\ManyNullify\Article';

    const TYPE_ONE_RESTRICT_CLASS = 'Fixture\ReferenceIntegrity\Document\OneRestrict\Type';
    const ARTICLE_ONE_RESTRICT_CLASS = 'Fixture\ReferenceIntegrity\Document\OneRestrict\Article';

    const TYPE_MANY_RESTRICT_CLASS = 'Fixture\ReferenceIntegrity\Document\ManyRestrict\Type';
    const ARTICLE_MANY_RESTRICT_CLASS = 'Fixture\ReferenceIntegrity\Document\ManyRestrict\Article';

    private $dm;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new ReferenceIntegrityListener());

        $this->dm = $this->createDocumentManager($evm);

        $this->populateOneNullify();
        $this->populateManyNullify();

        $this->populateOneRestrict();
        $this->populateManyRestrict();
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldHandleOneNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneByTitle('One Nullify Type');

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneByTitle('One Nullify Type');
        $this->assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_ONE_NULLIFY_CLASS)
            ->findOneByTitle('One Nullify Article');

        $this->assertNull($article->getType());

        $this->dm->clear();
    }

    /**
     * @test
     */
    function shouldHandleManyNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneByTitle('Many Nullify Type');

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneByTitle('Many Nullify Type');
        $this->assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_MANY_NULLIFY_CLASS)
            ->findOneByTitle('Many Nullify Article');

        $this->assertNull($article->getType());

        $this->dm->clear();
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\ReferenceIntegrityStrictException
     */
    function expectExceptionOneRestrict()
    {
        $type = $this->dm->getRepository(self::TYPE_ONE_RESTRICT_CLASS)
            ->findOneByTitle('One Restrict Type');

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\ReferenceIntegrityStrictException
     */
    function expectExceptionManyRestrict()
    {
        $type = $this->dm->getRepository(self::TYPE_MANY_RESTRICT_CLASS)
            ->findOneByTitle('Many Restrict Type');

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();
    }

    private function populateOneNullify()
    {
        $typeClass = self::TYPE_ONE_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Nullify Type');

        $articleClass = self::ARTICLE_ONE_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyNullify()
    {
        $typeClass = self::TYPE_MANY_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Nullify Type');

        $articleClass = self::ARTICLE_MANY_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOneRestrict()
    {
        $typeClass = self::TYPE_ONE_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Restrict Type');

        $articleClass = self::ARTICLE_ONE_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyRestrict()
    {
        $typeClass = self::TYPE_MANY_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Restrict Type');

        $articleClass = self::ARTICLE_MANY_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }
}
