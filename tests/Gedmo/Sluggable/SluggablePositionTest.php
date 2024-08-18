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
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Position;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggablePositionTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testPositionedSlugOrder(): void
    {
        $meta = $this->em->getClassMetadata(Position::class);
        $repo = $this->em->getRepository(Position::class);

        $object = $repo->find(1);
        $slug = $meta->getReflectionProperty('slug')->getValue($object);
        static::assertSame('code-other-title-prop', $slug);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Position::class,
        ];
    }

    private function populate(): void
    {
        $meta = $this->em->getClassMetadata(Position::class);
        $object = new Position();
        $meta->getReflectionProperty('title')->setValue($object, 'title');
        $meta->getReflectionProperty('prop')->setValue($object, 'prop');
        $meta->getReflectionProperty('code')->setValue($object, 'code');
        $meta->getReflectionProperty('other')->setValue($object, 'other');

        $this->em->persist($object);
        $this->em->flush();
    }
}
