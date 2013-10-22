<?php

namespace Gedmo\SoftDeleteable;

use SoftDeleteable\Fixture\Document\UsingTrait;

/**
 * Test for SoftDeletable Entity Trait
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeletableDocumentTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UsingTrait
     */
    protected $entity;

    public function setUp()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }
    }

    public function testGetSetDeletedAt()
    {
        $time = new \DateTime();
        $entity = new UsingTrait();

        $this->assertNull($entity->getDeletedAt(), 'deletedAt defaults to null');
        $this->assertFalse($entity->isDeleted(), 'isDeleted defaults to false');
        $this->assertSame($entity, $entity->setDeletedAt($time), 'Setter has a fluid interface');
        $this->assertSame($time, $entity->getDeletedAt(), 'Getter returns a DateTime Object');
        $this->assertTrue($entity->isDeleted(), 'Is deleted is true when deleteAt is not equal to null');
        $this->assertSame($entity, $entity->setDeletedAt(), 'Setting deletedAt to null undeletes object');
        $this->assertFalse($entity->isDeleted(), 'isDeleted should now return false');
    }
}
