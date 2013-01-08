<?php

namespace Gedmo\Uploadable\Mapping;

use Gedmo\Uploadable\Mapping\Validator;

/**
 * These are tests for the Mapping Validator of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $meta;

    public function setUp()
    {
        $this->meta = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);

        Validator::$enableMimeTypesConfigException = false;
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateField_ifFieldIsNotOfAValidTypeThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('type' => 'someType')));

        Validator::validateField(
            $this->meta,
            'someField',
            Validator::UPLOADABLE_FILE_MIME_TYPE,
            Validator::$validFileMimeTypeTypes
        );
    }

    /**
     * @expectedException Gedmo\Exception\UploadableInvalidPathException
     */
    public function test_validatePath_ifPathIsNotAStringOrIsAnEmptyStringThrowException()
    {
        Validator::validatePath('');
    }

    /**
     * @expectedException Gedmo\Exception\UploadableCantWriteException
     */
    public function test_validatePath_ifPassedDirIsNotAValidDirectoryOrIsNotWriteableThrowException()
    {
        Validator::validatePath('/invalid/directory/12312432423');
    }

    public function test_validatePath_ifPassedDirIsNotAValidDirectoryOrIsNotWriteableDoesNotThrowExceptionIfDisabled()
    {
        Validator::$validateWritableDirectory = false;
        Validator::validatePath('/invalid/directory/12312432423');
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifFilePathFieldIsNotDefinedThrowException()
    {
        $config = array('filePathField' => false);

        Validator::validateConfiguration($this->meta, $config);
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifPathMethodIsNotAValidMethodThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = array('filePathField' => 'someField', 'pathMethod' => 'invalidMethod');

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifCallbackMethodIsNotAValidMethodThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = array('filePathField' => 'someField', 'pathMethod' => '', 'callback' => 'invalidMethod');

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifFilenameGeneratorValueIsNotValidThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('type' => 'someType')));

        $config = array(
            'fileMimeTypeField' => '',
            'fileSizeField'     => '',
            'filePathField'     => 'someField',
            'pathMethod'        => '',
            'callback'          => '',
            'filenameGenerator' => 'invalidClass',
            'maxSize'           => 0,
            'allowedTypes'      => '',
            'disallowedTypes'   => ''
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifFilenameGeneratorValueIsValidButDoesntImplementNeededInterfaceThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('type' => 'someType')));

        $config = array(
            'fileMimeTypeField' => '',
            'fileSizeField'     => '',
            'filePathField'     => 'someField',
            'pathMethod'        => '',
            'callback'          => '',
            'filenameGenerator' => 'DateTime',
            'maxSize'           => 0,
            'allowedTypes'      => '',
            'disallowedTypes'   => ''
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function test_validateConfiguration_ifFilenameGeneratorValueIsValidThenDontThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('type' => 'string')));

        $config = array(
            'fileMimeTypeField' => '',
            'fileSizeField'     => '',
            'filePathField'     => 'someField',
            'pathMethod'        => '',
            'callback'          => '',
            'filenameGenerator' => 'SHA1',
            'maxSize'           => 0,
            'allowedTypes'      => '',
            'disallowedTypes'   => ''
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function test_validateConfiguration_ifFilenameGeneratorValueIsAValidClassThenDontThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('type' => 'string')));

        $config = array(
            'fileMimeTypeField' => '',
            'fileSizeField'     => '',
            'filePathField'     => 'someField',
            'pathMethod'        => '',
            'callback'          => '',
            'filenameGenerator' => 'Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorSha1',
            'maxSize'           => 0,
            'allowedTypes'      => '',
            'disallowedTypes'   => ''
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifMaxSizeIsLessThanZeroThenThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = array(
            'fileMimeTypeField' => 'someField',
            'filePathField'     => 'someField',
            'fileSizeField'     => '',
            'pathMethod'        => '',
            'callback'          => '',
            'maxSize'           => -123,
            'allowedTypes'      => '',
            'disallowedTypes'   => ''
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifAllowedTypesAndDisallowedTypesAreSetThenThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        Validator::$enableMimeTypesConfigException = true;

        $config = array(
            'fileMimeTypeField' => 'someField',
            'filePathField'     => 'someField',
            'fileSizeField'     => '',
            'pathMethod'        => '',
            'callback'          => '',
            'maxSize'           => 0,
            'allowedTypes'      => 'text/plain',
            'disallowedTypes'   => 'text/css'
        );

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }
}

class FakeEntity
{
}
