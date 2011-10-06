<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Translatable\Fixture\StringIdentifier;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableIdentifierTest extends BaseTestCaseORM
{
    const FIXTURE = 'Translatable\\Fixture\\StringIdentifier';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $testObjectId;
    private $translationListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $this->translationListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testStringIdentifier()
    {
        $object = new StringIdentifier();
        $object->setTitle('title in en');
        $object->setUid(md5(self::FIXTURE . time()));

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();
        $this->testObjectId = $object->getUid();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $object = $this->em->find(self::FIXTURE, $this->testObjectId);

        $translations = $repo->findTranslations($object);
        $this->assertEquals(count($translations), 0);

        $object = $this->em->find(self::FIXTURE, $this->testObjectId);
        $object->setTitle('title in de');
        $object->setTranslatableLocale('de_de');

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);

        // test the entity load by translated title
        $object = $repo->findObjectByTranslatedField(
            'title',
            'title in de',
            self::FIXTURE
        );

        $this->assertEquals($this->testObjectId, $object->getUid());

        $translations = $repo->findTranslations($object);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        // dql test object hydration
        $q = $this->em->createQuery('SELECT si FROM ' . self::FIXTURE . ' si WHERE si.uid = :id');
        $data = $q->execute(
            array('id' => $this->testObjectId),
            \Doctrine\ORM\Query::HYDRATE_OBJECT
        );
        $this->assertEquals(count($data), 1);
        $object = $data[0];
        $this->assertEquals('title in en', $object->getTitle());

        $this->translationListener->setTranslatableLocale('de_de');
        $q = $this->em->createQuery('SELECT si FROM ' . self::FIXTURE . ' si WHERE si.uid = :id');
        $data = $q->execute(
            array('id' => $this->testObjectId),
            \Doctrine\ORM\Query::HYDRATE_OBJECT
        );
        $this->assertEquals(count($data), 1);
        $object = $data[0];
        $this->assertEquals('title in de', $object->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
            self::TRANSLATION
        );
    }
}
