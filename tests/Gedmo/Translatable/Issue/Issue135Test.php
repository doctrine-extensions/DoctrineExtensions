<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue135Test extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Article';
    const COMMENT = 'Translatable\\Fixture\\Comment';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testIssue135()
    {
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from(self::ARTICLE, 'a')
            ->add('where', $query->expr()->not($query->expr()->eq('a.title', ':title')))
            ->setParameter('title', 'NA')
        ;

        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $query = $query->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $count = 0;
        str_replace("locale = 'en'", '', $query->getSql(), $count);
        $this->assertEquals(0, $count);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT
        );
    }

    public function populate()
    {
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $text0 = new Article;
        $text0->setTitle('text0');

        $this->em->persist($text0);

        $text1 = new Article;
        $text1->setTitle('text1');

        $this->em->persist($text1);

        $na = new Article;
        $na->setTitle('NA');

        $this->em->persist($na);

        $out = new Article;
        $out->setTitle('Out');

        $this->em->persist($out);
        $this->em->flush();
        $this->translatableListener->setTranslatableLocale('es');

        $text1->setTitle('texto1');
        $text0->setTitle('texto0');
        $this->em->persist($text1);
        $this->em->persist($text0);
        $this->em->flush();
    }
}
