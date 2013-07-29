<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Identifier;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableIdentifierTest extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Identifier';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::TARGET,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldBePossibleToSlugIdentifiers()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        $this->assertEquals('sport', $sport->getId());
    }

    /**
     * @test
     */
    public function shouldPersistMultipleNonConflictingIdentifierSlugs()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier();
        $sport2->setTitle('Sport');
        $this->em->persist($sport2);
        $this->em->flush();

        $this->assertEquals('sport', $sport->getId());
        $this->assertEquals('sport_1', $sport2->getId());
    }
}
