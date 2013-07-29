<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Validate;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class AnnotationValidationTest extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Validate';

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
            self::TARGET,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     * @expectedException \Gedmo\Exception\InvalidMappingException
     */
    public function shouldFailValidationOnInvalidAnnotation()
    {
        $slug = new Validate();
        $slug->setTitle('My Slug');

        $slug2 = new Validate();
        $slug2->setTitle('My Slug');

        $this->em->persist($slug);
        $this->em->persist($slug2);
        $this->em->flush();

        $this->assertEquals('my-slug', $slug2->getSlug());
    }
}
