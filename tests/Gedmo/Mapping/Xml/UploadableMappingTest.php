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
use Gedmo\Tests\Mapping\Fixture\Xml\Uploadable;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\UploadableListener;

/**
 * These are mapping tests for Uploadable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UploadableMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var UploadableListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        Validator::$enableMimeTypesConfigException = false;

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->listener = new UploadableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->listener);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            Uploadable::class,
        ], $chain);
    }

    public function testMetadata(): void
    {
        $meta = $this->em->getClassMetadata(Uploadable::class);
        $config = $this->listener->getConfiguration($this->em, $meta->getName());

        static::assertTrue($config['uploadable']);
        static::assertTrue($config['allowOverwrite']);
        static::assertTrue($config['appendNumber']);
        static::assertSame('/my/path', $config['path']);
        static::assertSame('getPath', $config['pathMethod']);
        static::assertSame('mimeType', $config['fileMimeTypeField']);
        static::assertSame('path', $config['filePathField']);
        static::assertSame('size', $config['fileSizeField']);
        static::assertSame('callbackMethod', $config['callback']);
        static::assertSame('SHA1', $config['filenameGenerator']);
        static::assertSame(1500.0, $config['maxSize']);
        static::assertContains('text/plain', $config['allowedTypes']);
        static::assertContains('text/css', $config['allowedTypes']);
        static::assertContains('video/jpeg', $config['disallowedTypes']);
        static::assertContains('text/html', $config['disallowedTypes']);
    }
}
