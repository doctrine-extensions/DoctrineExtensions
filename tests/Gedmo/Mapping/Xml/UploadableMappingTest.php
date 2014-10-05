<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
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

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');

        $this->listener = new UploadableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->listener);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Xml\Uploadable',
        ), $chain);
    }

    public function testMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Uploadable');
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
