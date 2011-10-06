<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Translatable\TranslationListener;
use Gedmo\Timestampable\TimestampableListener;
use Doctrine\Common\Util\Debug;
use Timestampable\Fixture\SupperClassExtension;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ProtectedPropertySupperclassTest extends BaseTestCaseORM
{
    const SUPERCLASS = "Timestampable\\Fixture\\SupperClassExtension";
    const TRANSLATION = "Gedmo\\Translatable\\Entity\\Translation";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $translationListener = new TranslationListener;
        $translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($translationListener);
        $evm->addEventSubscriber(new TimestampableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testProtectedProperty()
    {
        $test = new SupperClassExtension;
        $test->setName('name');
        $test->setTitle('title');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($test);
        $this->assertEquals(0, count($translations));

        $this->assertTrue($test->getCreatedAt() !== null);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TRANSLATION,
            self::SUPERCLASS
        );
    }
}
