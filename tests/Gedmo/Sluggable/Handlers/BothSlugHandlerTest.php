<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Handler\People\Occupation;
use Sluggable\Fixture\Handler\People\Person;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BothSlugHandlerTest extends BaseTestCaseORM
{
    const OCCUPATION = "Sluggable\\Fixture\\Handler\\People\\Occupation";
    const PERSON = "Sluggable\\Fixture\\Handler\\People\\Person";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::PERSON);

        $herzult = $repo->findOneByName('Herzult');
        $this->assertEquals('web/developer/php/herzult', $herzult->getSlug());

        $gedi = $repo->findOneByName('Gedi');
        $this->assertEquals('web/developer/gedi', $gedi->getSlug());

        $hurty = $repo->findOneByName('Hurty');
        $this->assertEquals('singer/hurty', $hurty->getSlug());
    }

    public function testSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::PERSON);

        $gedi = $repo->findOneByName('Gedi');
        $gedi->setName('Upd Gedi');
        $this->em->persist($gedi);
        $this->em->flush();

        $this->assertEquals('web/developer/upd-gedi', $gedi->getSlug());

        $artist = $this->em->getRepository(self::OCCUPATION)->findOneByTitle('Singer');
        $artist->setTitle('Artist');

        $this->em->persist($artist);
        $this->em->flush();

        $gedi->setOccupation($artist);
        $this->em->persist($gedi);
        $this->em->flush();

        $this->assertEquals('artist/upd-gedi', $gedi->getSlug());

        $hurty = $repo->findOneByName('Hurty');
        $this->assertEquals('artist/hurty', $hurty->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::OCCUPATION,
            self::PERSON
        );
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::OCCUPATION);

        $web = new Occupation;
        $web->setTitle('Web');

        $developer = new Occupation;
        $developer->setTitle('Developer');

        $designer = new Occupation;
        $designer->setTitle('Designer');

        $php = new Occupation;
        $php->setTitle('PHP');

        $singer = new Occupation;
        $singer->setTitle('Singer');

        $rock = new Occupation;
        $rock->setTitle('Rock');

        $repo
            ->persistAsFirstChild($web)
            ->persistAsFirstChild($singer)
            ->persistAsFirstChildOf($developer, $web)
            ->persistAsFirstChildOf($designer, $web)
            ->persistAsLastChildOf($php, $developer)
            ->persistAsLastChildOf($rock, $singer)
        ;

        $herzult = new Person;
        $herzult->setName('Herzult');
        $herzult->setOccupation($php);
        $this->em->persist($herzult);

        $gedi = new Person;
        $gedi->setName('Gedi');
        $gedi->setOccupation($developer);
        $this->em->persist($gedi);

        $hurty = new Person;
        $hurty->setName('Hurty');
        $hurty->setOccupation($singer);
        $this->em->persist($hurty);

        $this->em->flush();
    }
}
