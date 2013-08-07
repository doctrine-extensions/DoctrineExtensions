<?php

namespace Mapping\MetadataFactory;

use Gedmo\Mapping\ExtensionMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;

class ExtensionMetadataDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function shouldInitializeExtensionAnnotationDriver()
    {
        $driver = new AnnotationDriver(
            new CachedReader($reader = new AnnotationReader, new ArrayCache),
            array(__DIR__ . '/../../../Fixture')
        );

        $em = $this->getEntityManagerMock($driver);
        $emf = new ExtensionMetadataFactory($em, 'Gedmo\Fixture\EncoderExtension', $reader);

        $this->assertInstanceOf('Gedmo\Fixture\EncoderExtension\Mapping\Driver\Annotation', $emf->getExtensionDriver());
    }

    /**
     * @test
     */
    function shouldFallbackToAnnotationDriverIfTypeIsNotAvailable()
    {
        $driver = new YamlDriver(array(__DIR__));

        $em = $this->getEntityManagerMock($driver);
        $reader = $this->getMock('Doctrine\Common\Annotations\AnnotationReader');
        $emf = new ExtensionMetadataFactory($em, 'Gedmo\Fixture\EncoderExtension', $reader);

        $this->assertInstanceOf('Gedmo\Fixture\EncoderExtension\Mapping\Driver\Annotation', $emf->getExtensionDriver());
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\RuntimeException
     */
    function shouldThrowAnExceptionIfDriverIsNotFound()
    {
        $driver = new YamlDriver(array(__DIR__));

        $em = $this->getEntityManagerMock($driver);
        $reader = $this->getMock('Doctrine\Common\Annotations\AnnotationReader');
        $emf = new ExtensionMetadataFactory($em, 'Gedmo\Fixture\Unknown', $reader);
    }

    /**
     * @test
     */
    function simplifiedDriversShouldFallback()
    {
        $driver = new SimplifiedYamlDriver(array(__DIR__ => 'Namespace'));

        $em = $this->getEntityManagerMock($driver);
        $reader = $this->getMock('Doctrine\Common\Annotations\AnnotationReader');
        $emf = new ExtensionMetadataFactory($em, 'Gedmo\Translatable', $reader);

        $this->assertInstanceOf('Gedmo\Translatable\Mapping\Driver\Yaml', $emf->getExtensionDriver());

        $driver = new SimplifiedXmlDriver(array(__DIR__ => 'Namespace'));
        $em = $this->getEntityManagerMock($driver);
        $emf = new ExtensionMetadataFactory($em, 'Gedmo\Translatable', $reader);

        $this->assertInstanceOf('Gedmo\Translatable\Mapping\Driver\Xml', $emf->getExtensionDriver());
    }

    private function getEntityManagerMock(MappingDriver $driver)
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config
            ->expects($this->once())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($driver));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em
            ->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($config));

        return $em;
    }
}
