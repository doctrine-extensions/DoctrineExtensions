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

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifFilePathFieldIsNotDefinedThrowException()
    {
        Validator::validateConfiguration($this->meta, array('filePathField' => false));
    }

    /**
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function test_validateConfiguration_ifPathMethodIsNotAValidMethodThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        Validator::validateConfiguration(
            $this->meta,
            array('filePathField' => 'someField', 'pathMethod' => 'invalidMethod')
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

        Validator::validateConfiguration(
            $this->meta,
            array('filePathField' => 'someField', 'pathMethod' => '', 'callback' => 'invalidMethod')
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

        Validator::validateConfiguration(
            $this->meta,
            array(
                'fileMimeTypeField' => 'someField',
                'filePathField'     => 'someField',
                'pathMethod'        => '',
                'callback'          => '',
                'filenameGenerator' => 'invalidClass',
                'maxSize'           => 0
            )
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

        Validator::validateConfiguration(
            $this->meta,
            array(
                'fileMimeTypeField' => 'someField',
                'filePathField'     => 'someField',
                'pathMethod'        => '',
                'callback'          => '',
                'maxSize'           => -123
            )
        );
    }
}

class FakeEntity
{
}
