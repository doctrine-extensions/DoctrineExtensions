<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Mapping\Fixture\Xml\Translatable;
use Gedmo\Tests\Mapping\Fixture\Xml\TranslatableWithEmbedded;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var TranslatableListener
     */
    private $translatable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Translatable');
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->translatable = new TranslatableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->translatable);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            Translation::class,
            Translatable::class,
        ], $chain);
    }

    public function testTranslatableMetadata(): void
    {
        $meta = $this->em->getClassMetadata(Translatable::class);
        $config = $this->translatable->getConfiguration($this->em, $meta->getName());

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

    public function testTranslatableMetadataWithEmbedded(): void
    {
        $meta = $this->em->getClassMetadata(TranslatableWithEmbedded::class);
        $config = $this->translatable->getConfiguration($this->em, $meta->getName());

        static::assertContains('embedded.subtitle', $config['fields']);
    }
}
