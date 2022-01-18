<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Gedmo\Tests\Mapping\Fixture\Yaml\Referenced;
use Gedmo\Tests\Mapping\Fixture\Yaml\Referencer;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for ReferenceIntegrity extension
 *
 * @author Jonathan Eskew <jonathan@jeskew.net>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ReferenceIntegrityMappingTest extends BaseTestCaseOM
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ReferenceIntegrityListener
     */
    private $referenceIntegrity;

    protected function setUp(): void
    {
        static::markTestSkipped('Intentionally skipping test. Doctrine MongoDB ODM 2.0 removed the YAML mapping driver; skipping test until it can be rewritten using a supported mapper.');

        parent::setUp();

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $this->referenceIntegrity = new ReferenceIntegrityListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->referenceIntegrity);

        $this->dm = $this->getMockDocumentManager('gedmo_extensions_test', $yamlDriver);
    }

    public function testYamlMapping(): void
    {
        $referencerMeta = $this->dm->getClassMetadata(Referencer::class);
        $referenceeMeta = $this->dm->getClassMetadata(Referenced::class);
        $config = $this->referenceIntegrity->getConfiguration($this->dm, $referencerMeta->getName());

        static::assertNotEmpty($config['referenceIntegrity']);
        foreach ($config['referenceIntegrity'] as $propertyName => $referenceConfiguration) {
            static::assertArrayHasKey($propertyName, $referencerMeta->reflFields);

            foreach ($referenceConfiguration as $inversedPropertyName => $integrityType) {
                static::assertArrayHasKey($inversedPropertyName, $referenceeMeta->reflFields);
                static::assertContains($integrityType, ['nullify', 'restrict']);
            }
        }
    }
}
