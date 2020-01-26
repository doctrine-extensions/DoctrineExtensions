<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for ReferenceIntegrity extension
 *
 * @author Jonathan Eskew <jonathan@jeskew.net>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ReferenceIntegrityMappingTest extends BaseTestCaseOM
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ReferenceIntegrityListener
     */
    private $referenceIntegrity;

    public function setUp(): void
    {
        $this->markTestSkipped('Intentionally skipping test. Doctrine MongoDB ODM 2.0 removed the YAML mapping driver; skipping test until it can be rewritten using a supported mapper.');

        parent::setUp();

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $this->referenceIntegrity = new ReferenceIntegrityListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->referenceIntegrity);

        $this->dm = $this->getMockDocumentManager('gedmo_extensions_test', $yamlDriver);
    }

    public function testYamlMapping()
    {
        $referencerMeta = $this->dm->getClassMetadata('Mapping\Fixture\Yaml\Referencer');
        $referenceeMeta = $this->dm->getClassMetadata('Mapping\Fixture\Yaml\Referenced');
        $config = $this->referenceIntegrity->getConfiguration($this->dm, $referencerMeta->name);

        $this->assertNotEmpty($config['referenceIntegrity']);
        foreach ($config['referenceIntegrity'] as $propertyName => $referenceConfiguration) {
            $this->assertArrayHasKey($propertyName, $referencerMeta->reflFields);

            foreach ($referenceConfiguration as $inversedPropertyName => $integrityType) {
                $this->assertArrayHasKey($inversedPropertyName, $referenceeMeta->reflFields);
                $this->assertTrue(in_array($integrityType, ['nullify', 'restrict']));
            }
        }
    }
}
