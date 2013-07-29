<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Position;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggablePositionTest extends ObjectManagerTestCase
{
    const POSITION = 'Fixture\Sluggable\Position';

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
            self::POSITION,
        ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testPositionedSlugOrder()
    {
        $meta = $this->em->getClassMetadata(self::POSITION);
        $repo = $this->em->getRepository(self::POSITION);

        $object = $repo->find(1);
        $slug = $meta->getReflectionProperty('slug')->getValue($object);
        $this->assertEquals('code-other-title-prop', $slug);
    }

    private function populate()
    {
        $meta = $this->em->getClassMetadata(self::POSITION);
        $object = new Position();
        $meta->getReflectionProperty('title')->setValue($object, 'title');
        $meta->getReflectionProperty('prop')->setValue($object, 'prop');
        $meta->getReflectionProperty('code')->setValue($object, 'code');
        $meta->getReflectionProperty('other')->setValue($object, 'other');

        $this->em->persist($object);
        $this->em->flush();
    }
}
