<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Uploadable\UploadableListener;
use Gedmo\Uploadable\Mapping\Validator;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for Uploadable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class UploadableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\SoftDeleteable\UploadableListener
     */
    private $listener;

    public function setUp()
    {
        parent::setUp();

        Validator::$enableMimeTypesConfigException = false;
        
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain;
        $chain->addDriver($yamlDriver, 'Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');

        $this->listener = new UploadableListener();
        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->listener);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Yaml\Uploadable'
        ), $chain);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Yaml\Uploadable');
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        
        $this->assertTrue($config['uploadable']);
        $this->assertTrue($config['allowOverwrite']);
        $this->assertTrue($config['appendNumber']);
        $this->assertEquals('/my/path', $config['path']);
        $this->assertEquals('getPath', $config['pathMethod']);
        $this->assertEquals('mimeType', $config['fileMimeTypeField']);
        $this->assertEquals('path', $config['filePathField']);
        $this->assertEquals('size', $config['fileSizeField']);
        $this->assertEquals('callbackMethod', $config['callback']);
        $this->assertEquals('SHA1', $config['filenameGenerator']);
        $this->assertEquals(1500, $config['maxSize']);
        $this->assertContains('text/plain', $config['allowedTypes']);
        $this->assertContains('text/css', $config['allowedTypes']);
        $this->assertContains('video/jpeg', $config['disallowedTypes']);
        $this->assertContains('text/html', $config['disallowedTypes']);
    }
}
