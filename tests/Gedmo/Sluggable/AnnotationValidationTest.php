<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Validate;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class AnnotationValidationTest extends BaseTestCaseORM
{
    public const TARGET = 'Gedmo\\Tests\\Sluggable\\Fixture\\Validate';

    /**
     * @test
     */
    public function shouldFailValidationOnInvalidAnnotation()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
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
        return [
            self::TARGET,
        ];
    }
}
