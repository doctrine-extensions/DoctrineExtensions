<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Validate;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class AnnotationValidationTest extends BaseTestCaseORM
{
    public const TARGET = Validate::class;

    /**
     * @test
     */
    public function shouldFailValidationOnInvalidAnnotation()
    {
        $this->expectException(InvalidMappingException::class);
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

        static::assertSame('my-slug', $slug2->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
