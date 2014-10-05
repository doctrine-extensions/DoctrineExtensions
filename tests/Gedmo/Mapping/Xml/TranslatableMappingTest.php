<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Translatable\TranslatableListener
     */
    private $translatable;

    public function setUp()
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Translatable');
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');

        $this->translatable = new TranslatableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->translatable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Gedmo\Translatable\Entity\Translation',
            'Mapping\Fixture\Xml\Translatable',
        ), $chain);
    }

    public function testTranslatableMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Translatable');
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('translationClass', $config);
        $this->assertEquals('Gedmo\Translatable\Entity\Translation', $config['translationClass']);
        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('locale', $config['locale']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(4, $config['fields']);
        $this->assertTrue(in_array('title', $config['fields']));
        $this->assertTrue(in_array('content', $config['fields']));
        $this->assertTrue(in_array('author', $config['fields']));
        $this->assertTrue(in_array('views', $config['fields']));
        $this->assertTrue($config['fallback']['author']);
        $this->assertFalse($config['fallback']['views']);
    }
}
