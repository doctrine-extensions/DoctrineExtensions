<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Tests\Mapping\Fixture\Xml\Translatable;
use Gedmo\Tests\Mapping\Fixture\Xml\TranslatableWithEmbedded;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Translatable\Entity\Translation;
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
final class TranslatableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Translatable\TranslatableListener
     */
    private $translatable;

    protected function setUp(): void
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
            Translation::class,
            Translatable::class,
        ], $chain);
    }

    public function testTranslatableMetadata()
    {
        $meta = $this->em->getClassMetadata(Translatable::class);
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('translationClass', $config);
        static::assertSame(Translation::class, $config['translationClass']);
        static::assertArrayHasKey('locale', $config);
        static::assertSame('locale', $config['locale']);

        static::assertArrayHasKey('fields', $config);
        static::assertCount(4, $config['fields']);
        static::assertContains('title', $config['fields']);
        static::assertContains('content', $config['fields']);
        static::assertContains('author', $config['fields']);
        static::assertContains('views', $config['fields']);
        static::assertTrue($config['fallback']['author']);
        static::assertFalse($config['fallback']['views']);
    }

    public function testTranslatableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata(TranslatableWithEmbedded::class);
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        static::assertContains('embedded.subtitle', $config['fields']);
    }
}
