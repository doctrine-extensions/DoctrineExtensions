<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Gedmo\Tests\SoftDeleteable\Fixture\Entity\UsingTrait;

/**
 * Test for SoftDeletable Entity Trait
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SoftDeletableEntityTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UsingTrait
     */
    protected $entity;

    public function testGetSetDeletedAt(): void
    {
        $time = new \DateTime();
        $entity = new UsingTrait();

        static::assertNull($entity->getDeletedAt(), 'deletedAt defaults to null');
        static::assertFalse($entity->isDeleted(), 'isDeleted defaults to false');
        static::assertSame($entity, $entity->setDeletedAt($time), 'Setter has a fluid interface');
        static::assertSame($time, $entity->getDeletedAt(), 'Getter returns a DateTime Object');
        static::assertTrue($entity->isDeleted(), 'Is deleted is true when deleteAt is not equal to null');
        static::assertSame($entity, $entity->setDeletedAt(), 'Setting deletedAt to null undeletes object');
        static::assertFalse($entity->isDeleted(), 'isDeleted should now return false');
    }
}
