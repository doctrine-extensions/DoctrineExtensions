<?php

namespace Gedmo\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\Sluggable\Issue939\SluggableListener;
use Gedmo\Fixture\Sluggable\Issue939\Article;
use Gedmo\Fixture\Sluggable\Issue939\Category;
use Gedmo\TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue939Test extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Sluggable\Issue939\Article';
    const CATEGORY = 'Gedmo\Fixture\Sluggable\Issue939\Category';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
            self::CATEGORY,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testSlugGeneration()
    {
        $category = new Category();
        $category->setTitle('Misc articles');
        $this->em->persist($category);

        $article = new Article();
        $article->setTitle('Is there water on the moon?');
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals('Is there water on the moon?', $article->getSlug());
        $this->assertEquals('misc-articles', $category->getSlug());
    }
}
