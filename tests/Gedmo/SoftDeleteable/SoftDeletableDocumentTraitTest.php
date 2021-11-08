<?php

namespace Gedmo\Tests\SoftDeleteable;

use Gedmo\Tests\SoftDeleteable\Fixture\Document\UsingTrait;

/**
 * Test for SoftDeletable Entity Trait
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SoftDeletableDocumentTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UsingTrait
     */
    protected $entity;

    public function testGetSetDeletedAt()
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
