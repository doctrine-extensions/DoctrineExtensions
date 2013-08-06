<?php

namespace SluggableMapping;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Sluggable\Genealogy\Person;
use Fixture\Sluggable\Genealogy\Man;
use Fixture\Sluggable\Genealogy\Woman;
use Fixture\Sluggable\Genealogy\Employee;
use Gedmo\Sluggable\SluggableListener;

class InheritedAnnotionTest extends ObjectManagerTestCase
{
    const PERSON = 'Fixture\Sluggable\Genealogy\Person';
    const MAN = 'Fixture\Sluggable\Genealogy\Man';
    const WOMAN = 'Fixture\Sluggable\Genealogy\Woman';
    const EMPLOYEE = 'Fixture\Sluggable\Genealogy\Employee';

    private $em, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new SluggableListener);
        $this->em = $this->createEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldMapInheritedSlugFields()
    {
        $personMeta = $this->em->getClassMetadata(self::PERSON);
        $pem = $this->listener->getConfiguration($this->em, $personMeta->name);

        $this->assertCount(1, $slugs = $pem->getFields());
        $this->assertSame('uri', $slugs[0]);
        $this->assertTrue(is_array($options = $pem->getOptions('uri')));

        $employeeMeta = $this->em->getClassMetadata(self::EMPLOYEE);
        $eem = $this->listener->getConfiguration($this->em, $employeeMeta->name);

        $this->assertCount(3, $slugs = $eem->getFields());
        $this->assertSame('uri', $slugs[0]);
        $this->assertSame('slug', $slugs[1]);
        $this->assertSame('workerSlug', $slugs[2]);

        $this->assertTrue(is_array($options = $eem->getOptions('uri')));
        $this->assertSame(self::PERSON, $options['rootClass']);

        $this->assertTrue(is_array($options = $eem->getOptions('slug')));
        $this->assertSame(self::MAN, $options['rootClass']);

        $this->assertTrue(is_array($options = $eem->getOptions('workerSlug')));
        $this->assertSame(self::EMPLOYEE, $options['rootClass']);

        $womanMeta = $this->em->getClassMetadata(self::WOMAN);
        $wem = $this->listener->getConfiguration($this->em, $womanMeta->name);

        $this->assertCount(1, $slugs = $wem->getFields());
        $this->assertSame('uri', $slugs[0]);
        $this->assertTrue(is_array($options = $wem->getOptions('uri')));

        $this->assertTrue(is_array($options = $wem->getOptions('uri')));
        $this->assertSame(self::PERSON, $options['rootClass']);

        $manMeta = $this->em->getClassMetadata(self::MAN);
        $mem = $this->listener->getConfiguration($this->em, $manMeta->name);

        $this->assertCount(2, $slugs = $mem->getFields());
        $this->assertSame('uri', $slugs[0]);
        $this->assertSame('slug', $slugs[1]);

        $this->assertTrue(is_array($options = $mem->getOptions('uri')));
        $this->assertSame(self::PERSON, $options['rootClass']);

        $this->assertTrue(is_array($options = $mem->getOptions('slug')));
        $this->assertSame(self::MAN, $options['rootClass']);
    }
}
