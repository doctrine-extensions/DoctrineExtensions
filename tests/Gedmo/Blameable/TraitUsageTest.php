<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Blameable\Fixture\UsingTrait;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Blameable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TraitUsageTest extends BaseTestCaseORM
{
    const TARGET = "Blameable\\Fixture\\UsingTrait";

    protected function setUp()
    {
        parent::setUp();

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }

        $listener = new BlameableListener;
        $listener->setUserValue('testuser');
        $evm = new EventManager;
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldTimestampUsingTrait()
    {
        $sport = new UsingTrait;
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertNotNull($sport->getCreatedBy());
        $this->assertNotNull($sport->getUpdatedBy());
    }

    /**
     * @test
     */
    function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait;
        $this->assertInstanceOf('Blameable\Fixture\UsingTrait', $sport->setCreatedBy('myuser'));
        $this->assertInstanceOf('Blameable\Fixture\UsingTrait', $sport->setUpdatedBy('myuser'));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
    }
}
