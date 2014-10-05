<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Validate;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class AnnotationValidationTest extends BaseTestCaseORM
{
    const TARGET = 'Sluggable\\Fixture\\Validate';

    /**
     * @test
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function shouldFailValidationOnInvalidAnnotation()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->getMockSqliteEntityManager($evm);

        $slug = new Validate();
        $slug->setTitle('My Slug');

        $slug2 = new Validate();
        $slug2->setTitle('My Slug');

        $this->em->persist($slug);
        $this->em->persist($slug2);
        $this->em->flush();

        $this->assertEquals('my-slug', $slug2->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET,
        );
    }
}
