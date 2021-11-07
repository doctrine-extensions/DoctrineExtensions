<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
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

    public function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Translatable');
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->translatable = new TranslatableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->translatable);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Translatable\Entity\Translation',
            'Gedmo\Tests\Mapping\Fixture\Xml\Translatable',
        ], $chain);
    }

    public function testTranslatableMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\Translatable');
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('translationClass', $config);
        $this->assertEquals('Gedmo\Translatable\Entity\Translation', $config['translationClass']);
        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('locale', $config['locale']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(4, $config['fields']);
        $this->assertContains('title', $config['fields']);
        $this->assertContains('content', $config['fields']);
        $this->assertContains('author', $config['fields']);
        $this->assertContains('views', $config['fields']);
        $this->assertTrue($config['fallback']['author']);
        $this->assertFalse($config['fallback']['views']);
    }

    public function testTranslatableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\TranslatableWithEmbedded');
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertContains('embedded.subtitle', $config['fields']);
    }
}
