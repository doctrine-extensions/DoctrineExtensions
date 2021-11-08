<?php

namespace Gedmo\Tests\Uploadable\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Exception\UploadableInvalidPathException;
use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorSha1;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * These are tests for the Mapping Validator of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    protected $meta;

    protected function setUp(): void
    {
        $this->meta = $this->getMockBuilder(ClassMetadata::class)
            ->setConstructorArgs(['', null])
            ->getMock();

        Validator::$enableMimeTypesConfigException = false;
    }

    protected function tearDown(): void
    {
        Validator::$enableMimeTypesConfigException = true;
    }

    public function testValidateFieldIfFieldIsNotOfAValidTypeThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getFieldMapping')
            ->willReturn(['type' => 'someType']);

        Validator::validateField(
            $this->meta,
            'someField',
            Validator::UPLOADABLE_FILE_MIME_TYPE,
            Validator::$validFileMimeTypeTypes
        );
    }

    public function testValidatePathIfPathIsNotAStringOrIsAnEmptyStringThrowException()
    {
        $this->expectException(UploadableInvalidPathException::class);
        Validator::validatePath('');
    }

    public function testValidatePathCreatesNewDirectoryWhenItNotExists()
    {
        $dir = TESTS_TEMP_DIR.'/new/directory-12312432423';
        Validator::validatePath($dir);
        static::assertDirectoryExists($dir);
        rmdir($dir);
        rmdir(dirname($dir));
    }

    public function testValidateConfigurationIfNeitherFilePathFieldNorFileNameFieldIsNotDefinedThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $config = ['filePathField' => false, 'fileNameField' => false];

        Validator::validateConfiguration($this->meta, $config);
    }

    public function testValidateConfigurationIfPathMethodIsNotAValidMethodThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));

        $config = ['filePathField' => 'someField', 'pathMethod' => 'invalidMethod'];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfCallbackMethodIsNotAValidMethodThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));

        $config = ['filePathField' => 'someField', 'pathMethod' => '', 'callback' => 'invalidMethod'];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsNotValidThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));
        $this->meta
            ->method('getFieldMapping')
            ->willReturn(['type' => 'someType']);

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'invalidClass',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsValidButDoesntImplementNeededInterfaceThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));
        $this->meta
            ->method('getFieldMapping')
            ->willReturn(['type' => 'someType']);

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'DateTime',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsValidThenDontThrowException()
    {
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));
        $this->meta
            ->method('getFieldMapping')
            ->willReturn(['type' => 'string']);

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'SHA1',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsAValidClassThenDontThrowException()
    {
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));
        $this->meta
            ->method('getFieldMapping')
            ->willReturn(['type' => 'string']);

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => FilenameGeneratorSha1::class,
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfMaxSizeIsLessThanZeroThenThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));

        $config = [
            'fileMimeTypeField' => 'someField',
            'filePathField' => 'someField',
            'fileSizeField' => '',
            'pathMethod' => '',
            'callback' => '',
            'maxSize' => -123,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfAllowedTypesAndDisallowedTypesAreSetThenThrowException()
    {
        $this->expectException(InvalidMappingException::class);
        $this->meta->expects(static::once())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(new FakeEntity()));

        Validator::$enableMimeTypesConfigException = true;

        $config = [
            'fileMimeTypeField' => 'someField',
            'filePathField' => 'someField',
            'fileSizeField' => '',
            'pathMethod' => '',
            'callback' => '',
            'maxSize' => 0,
            'allowedTypes' => 'text/plain',
            'disallowedTypes' => 'text/css',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }
}

class FakeEntity
{
}
