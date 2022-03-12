<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable;

use Doctrine\Common\EventManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UploadableCantWriteException;
use Gedmo\Exception\UploadableCouldntGuessMimeTypeException;
use Gedmo\Exception\UploadableExtensionException;
use Gedmo\Exception\UploadableFileAlreadyExistsException;
use Gedmo\Exception\UploadableFormSizeException;
use Gedmo\Exception\UploadableIniSizeException;
use Gedmo\Exception\UploadableInvalidMimeTypeException;
use Gedmo\Exception\UploadableMaxSizeException;
use Gedmo\Exception\UploadableNoFileException;
use Gedmo\Exception\UploadableNoPathDefinedException;
use Gedmo\Exception\UploadableNoTmpDirException;
use Gedmo\Exception\UploadablePartialException;
use Gedmo\Exception\UploadableUploadException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Uploadable\Fixture\Entity\Article;
use Gedmo\Tests\Uploadable\Fixture\Entity\File;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileAppendNumber;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileAppendNumberRelative;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithAllowedTypes;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithAlphanumericName;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithCustomFilenameGenerator;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithDisallowedTypes;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithMaxSize;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithoutPath;
use Gedmo\Tests\Uploadable\Fixture\Entity\FileWithSha1Name;
use Gedmo\Tests\Uploadable\Fixture\Entity\Image;
use Gedmo\Tests\Uploadable\Stub\FileInfoStub;
use Gedmo\Tests\Uploadable\Stub\MimeTypeGuesserStub;
use Gedmo\Tests\Uploadable\Stub\UploadableListenerStub;
use Gedmo\Uploadable\FileInfo\FileInfoArray;
use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface;

/**
 * These are tests for Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UploadableEntityTest extends BaseTestCaseORM
{
    public const IMAGE_CLASS = Image::class;
    public const ARTICLE_CLASS = Article::class;
    public const FILE_CLASS = File::class;
    public const FILE_APPEND_NUMBER_CLASS = FileAppendNumber::class;
    public const FILE_APPEND_NUMBER__RELATIVE_PATH_CLASS = FileAppendNumberRelative::class;
    public const FILE_WITHOUT_PATH_CLASS = FileWithoutPath::class;
    public const FILE_WITH_SHA1_NAME_CLASS = FileWithSha1Name::class;
    public const FILE_WITH_ALPHANUMERIC_NAME_CLASS = FileWithAlphanumericName::class;
    public const FILE_WITH_CUSTOM_FILENAME_GENERATOR_CLASS = FileWithCustomFilenameGenerator::class;
    public const FILE_WITH_MAX_SIZE_CLASS = FileWithMaxSize::class;
    public const FILE_WITH_ALLOWED_TYPES_CLASS = FileWithAllowedTypes::class;
    public const FILE_WITH_DISALLOWED_TYPES_CLASS = FileWithDisallowedTypes::class;

    /**
     * @var UploadableListenerStub
     */
    private $listener;

    /** @var string */
    private $testFile;

    /** @var string */
    private $testFile2;

    /** @var string */
    private $testFile3;

    /** @var string */
    private $testFileWithoutExt;

    /** @var string */
    private $testFileWithSpaces;

    /** @var string */
    private $destinationTestDir;

    /** @var string */
    private $destinationTestFile;

    /** @var false|string */
    private $testFilename;

    /** @var false|string */
    private $testFilename2;

    /** @var false|string */
    private $testFilename3;

    /** @var false|string */
    private $testFilenameWithoutExt;

    /** @var false|string */
    private $testFilenameWithSpaces;

    /** @var int */
    private $testFileSize;

    /** @var string */
    private $testFileMimeType;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->listener = new UploadableListenerStub();
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/plain'));

        $evm->addEventSubscriber($this->listener);
        $config = $this->getDefaultConfiguration();
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);
        $this->testFile = TESTS_PATH.'/data/test.txt';
        $this->testFile2 = TESTS_PATH.'/data/test2.txt';
        $this->testFile3 = TESTS_PATH.'/data/test_3.txt';
        $this->testFileWithoutExt = TESTS_PATH.'/data/test4';
        $this->testFileWithSpaces = TESTS_PATH.'/data/test with spaces.txt';
        $this->destinationTestDir = TESTS_TEMP_DIR.'/uploadable';
        $this->destinationTestFile = $this->destinationTestDir.'/test.txt';
        $this->testFilename = substr($this->testFile, strrpos($this->testFile, '/') + 1);
        $this->testFilename2 = substr($this->testFile2, strrpos($this->testFile2, '/') + 1);
        $this->testFilename3 = substr($this->testFile3, strrpos($this->testFile3, '/') + 1);
        $this->testFilenameWithoutExt = substr($this->testFileWithoutExt, strrpos($this->testFileWithoutExt, '/') + 1);
        $this->testFilenameWithSpaces = substr($this->testFileWithSpaces, strrpos($this->testFileWithSpaces, '/') + 1);
        $this->testFileSize = 4;
        $this->testFileMimeType = 'text/plain';

        $this->clearFilesAndDirectories();

        if (!is_dir($this->destinationTestDir)) {
            mkdir($this->destinationTestDir);
        }
    }

    protected function tearDown(): void
    {
        $this->clearFilesAndDirectories();
    }

    public function testUploadableEntity(): void
    {
        // INSERTION of an Uploadable Entity

        // If there was no uploaded file, we do nothing
        $image = new Image();
        $image->setTitle('123');

        $this->em->persist($image);
        $this->em->flush();

        static::assertNull($image->getFilePath());

        // If there is an uploaded file, we process it
        $fileInfo = $this->generateUploadedFile();

        $image2 = new Image();
        $image2->setTitle('456');
        $this->listener->addEntityFileInfo($image2, $fileInfo);

        $this->em->persist($image2);
        $this->em->flush();

        $this->em->refresh($image2);

        // We need to set this again because of the recent refresh
        $firstFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath().'/'.$fileInfo['name'], $image2->getFilePath());
        static::assertTrue(is_file($firstFile));
        static::assertSame((string) $fileInfo['size'], $image2->getSize());
        static::assertSame($fileInfo['type'], $image2->getMime());

        // UPDATE of an Uploadable Entity

        // We change the "uploaded" file
        $fileInfo['tmp_name'] = $this->testFile2;
        $fileInfo['name'] = $this->testFilename2;

        // We use a FileInfoInterface instance here
        $this->listener->addEntityFileInfo($image2, new FileInfoArray($fileInfo));

        $this->em->flush();

        $this->em->refresh($image2);

        $lastFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath().'/'.$fileInfo['name'], $image2->getFilePath());
        static::assertTrue(is_file($lastFile));

        // First file should be removed on update
        static::assertFalse(is_file($firstFile));

        // REMOVAL of an Uploadable Entity
        $this->em->remove($image2);
        $this->em->flush();

        static::assertFalse(is_file($lastFile));
    }

    public function testUploadableEntityWithCompositePath(): void
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        // If there is an uploaded file, we process it
        $fileInfo = $this->generateUploadedFile();

        $image2 = new Image();
        $image2->setUseBasePath(true);
        $image2->setTitle('456');
        $this->listener->addEntityFileInfo($image2, $fileInfo);

        $this->em->persist($image2);
        $this->em->flush();

        $this->em->refresh($image2);

        // We need to set this again because of the recent refresh
        $firstFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath($this->destinationTestDir).'/'.$fileInfo['name'], $image2->getFilePath());
        static::assertTrue(is_file($firstFile));
        static::assertSame((string) $fileInfo['size'], $image2->getSize());
        static::assertSame($fileInfo['type'], $image2->getMime());

        // UPDATE of an Uploadable Entity

        // We change the "uploaded" file
        $fileInfo['tmp_name'] = $this->testFile2;
        $fileInfo['name'] = $this->testFilename2;

        // We use a FileInfoInterface instance here
        $this->listener->addEntityFileInfo($image2, new FileInfoArray($fileInfo));

        $this->em->flush();

        $this->em->refresh($image2);

        $lastFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath($this->destinationTestDir).'/'.$fileInfo['name'], $image2->getFilePath());
        static::assertTrue(is_file($lastFile));

        // First file should be removed on update
        static::assertFalse(is_file($firstFile));

        // REMOVAL of an Uploadable Entity
        $this->em->remove($image2);
        $this->em->flush();

        static::assertFalse(is_file($lastFile));
    }

    public function testEntityWithUploadableEntities(): void
    {
        $artRepo = $this->em->getRepository(self::ARTICLE_CLASS);
        $article = new Article();
        $article->setTitle('Test');

        $file1 = new File();
        $file2 = new File();
        $file3 = new File();

        $article->addFile($file1);
        $article->addFile($file2);
        $article->addFile($file3);

        $filesArrayIndex = 'file';

        $fileInfo = $this->generateUploadedFile();
        $fileInfo2 = $this->generateUploadedFile();
        $fileInfo3 = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file1, $fileInfo);
        $this->listener->addEntityFileInfo($file2, $fileInfo2);
        $this->listener->addEntityFileInfo($file3, $fileInfo3);

        $this->em->persist($article);

        $this->em->flush();

        $art = $artRepo->findOneBy(['title' => 'Test']);
        $files = $art->getFiles();
        $file1Path = $file1->getPath().'/'.$fileInfo['name'];
        $file2Path = $file2->getPath().'/'.$fileInfo['name'];
        $file3Path = $file3->getPath().'/'.$fileInfo['name'];

        $this->assertPathEquals($file1Path, $files[0]->getFilePath());
        $this->assertPathEquals($file2Path, $files[1]->getFilePath());
        $this->assertPathEquals($file3Path, $files[2]->getFilePath());
    }

    public function testNoPathDefinedOnEntityOrListenerThrowsException(): void
    {
        $this->expectException(UploadableNoPathDefinedException::class);
        $file = new FileWithoutPath();

        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testNoPathDefinedOnEntityButDefinedOnListenerUsesDefaultPath(): void
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithoutPath();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $this->assertPathEquals($this->destinationTestFile, $file->getFilePath());
    }

    public function testCallbackIsCalledIfItsSetOnEntity(): void
    {
        $file = new File();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        static::assertTrue($file->callbackWasCalled);
    }

    /**
     * @dataProvider uploadExceptionsProvider
     */
    public function testUploadExceptions(int $error, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);

        $file = new File();
        $fileInfo = $this->generateUploadedFile();
        $fileInfo['error'] = $error;

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testSettingAnotherDefaultFileInfoClass(): void
    {
        $fileInfoStubClass = FileInfoStub::class;

        $this->listener->setDefaultFileInfoClass($fileInfoStubClass);

        $file = new File();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);
        $fileInfo = $this->listener->getEntityFileInfo($file);

        static::assertInstanceOf($fileInfoStubClass, $fileInfo);
    }

    public function testFileWithFilenameSha1Generator(): void
    {
        $file = new FileWithSha1Name();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $sha1String = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);
        $sha1String = str_replace('.txt', '', $sha1String);

        // @todo: Remove the condition and the `else` block when dropping support for "phpunit/phpunit" < 9.1.
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            static::assertMatchesRegularExpression('/[a-z0-9]{40}/', $sha1String);
        } else {
            static::assertRegExp('/[a-z0-9]{40}/', $sha1String);
        }
    }

    public function testFileWithFilenameAlphanumericGenerator(): void
    {
        $file = new FileWithAlphanumericName();
        $fileInfo = $this->generateUploadedFile($this->testFile3, $this->testFilename3);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filename = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);

        static::assertSame('test-3.txt', $filename);
    }

    public function testFileWithCustomFilenameGenerator(): void
    {
        $file = new FileWithCustomFilenameGenerator();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filename = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);

        static::assertSame('123.txt', $filename);
    }

    public function testUploadFileWithoutExtension(): void
    {
        $file = new File();
        $fileInfo = $this->generateUploadedFile($this->testFileWithoutExt, $this->testFilenameWithoutExt);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filePath = $file->getPath().'/'.$fileInfo['name'];

        $this->assertPathEquals($filePath, $file->getFilePath());
    }

    public function testFileAlreadyExistsException(): void
    {
        $this->expectException(UploadableFileAlreadyExistsException::class);
        $file = new Image();
        $file->setTitle('test');
        $fileInfo = $this->generateUploadedFile($this->testFileWithoutExt, $this->testFilenameWithoutExt);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->flush();
    }

    public function testRemoveFileIfItsNotAFileThenReturnFalse(): void
    {
        static::assertFalse($this->listener->removeFile('non_existent_file'));
    }

    public function testMoveFileUsingAppendNumberOptionAppendsNumberToFilenameIfItAlreadyExists(): void
    {
        $file = new FileAppendNumber();
        $file2 = new FileAppendNumber();

        $file->setTitle('test');
        $file2->setTitle('test2');

        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->listener->addEntityFileInfo($file2, $fileInfo);

        $this->em->persist($file2);
        $this->em->flush();

        $this->em->refresh($file2);

        $filename = substr($file2->getFilePath(), strrpos($file2->getFilePath(), '/') + 1);

        static::assertSame('test-2.txt', $filename);
    }

    public function testMoveFileUsingAppendNumberOptionAppendsNumberToFilenameIfItAlreadyExistsRelativePath(): void
    {
        $currDir = __DIR__;
        chdir(realpath(TESTS_TEMP_DIR.'/uploadable'));
        $file = new FileAppendNumber();
        $file2 = new FileAppendNumberRelative();

        $file->setTitle('test');
        $file2->setTitle('test2');

        $fileInfo = $this->generateUploadedFile(realpath(TESTS_PATH.'/data/test'), 'test');

        $this->listener->addEntityFileInfo($file, $fileInfo);
        $this->em->persist($file);
        $this->em->flush();

        $this->listener->addEntityFileInfo($file2, $fileInfo);

        $this->em->persist($file2);
        $this->em->flush();

        $this->em->refresh($file2);

        static::assertSame('./test-2', $file2->getFilePath());

        chdir($currDir);
    }

    public function testMoveFileIfUploadedFileCantBeMovedThrowException(): void
    {
        $this->expectException(UploadableUploadException::class);
        $this->listener->returnFalseOnMoveUploadedFile = true;

        $file = new Image();
        $file->setTitle('test');
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testAddEntityFileInfoIfFileInfoIsNotValidThrowException(): void
    {
        $this->expectException('RuntimeException');
        $this->listener->addEntityFileInfo(new Image(), 'invalidFileInfo');
    }

    public function testGetEntityFileInfoIfTheresNoFileInfoForEntityThrowException(): void
    {
        $this->expectException('RuntimeException');
        $this->listener->getEntityFileInfo(new Image());
    }

    public function testFileExceedingMaximumAllowedSizeThrowsException(): void
    {
        $this->expectException(UploadableMaxSizeException::class);
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithMaxSize();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testFileNotExceedingMaximumAllowedSizeDoesntThrowException(): void
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithMaxSize();
        $size = 1;
        $fileInfo = $this->generateUploadedFile(false, false, ['size' => $size]);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        static::assertSame((string) $size, $file->getFileSize());
    }

    public function testIfMimeTypeGuesserCantResolveTypeThrowException(): void
    {
        $this->expectException(UploadableCouldntGuessMimeTypeException::class);
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub(null));

        $file = new FileWithAllowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testAllowedTypesOptionIfMimeTypeIsInvalidThrowException(): void
    {
        $this->expectException(UploadableInvalidMimeTypeException::class);
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/css'));

        $file = new FileWithAllowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testDisallowedTypesOptionIfMimeTypeIsInvalidThrowException(): void
    {
        $this->expectException(UploadableInvalidMimeTypeException::class);
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/css'));

        $file = new FileWithDisallowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    /**
     * @dataProvider invalidFileInfoClassesProvider
     */
    public function testSetDefaultFileInfoClassThrowExceptionIfInvalidClassArePassed($class): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->listener->setDefaultFileInfoClass($class);
    }

    public function testSetDefaultFileInfoClassSetClassIfClassIsValid(): void
    {
        $validClass = FileInfoArray::class;

        $this->listener->setDefaultFileInfoClass($validClass);

        static::assertSame($validClass, $this->listener->getDefaultFileInfoClass());
    }

    public function testUseGeneratedFilenameWhenAppendingNumbers(): void
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithAlphanumericName();
        $fileInfo = $this->generateUploadedFile($this->testFileWithSpaces, $this->testFilenameWithSpaces);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $filePath = $file->getPath().'/'.str_replace(' ', '-', $fileInfo['name']);

        $this->assertPathEquals($filePath, $file->getFilePath());

        $file = new FileWithAlphanumericName();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $filePath = $file->getPath().'/'.str_replace(' ', '-', str_replace('.txt', '-2.txt', $fileInfo['name']));

        $this->assertPathEquals($filePath, $file->getFilePath());
    }

    // Data Providers
    public function invalidFileInfoClassesProvider(): array
    {
        return [
            [''],
            [false],
            [null],
            ['FakeFileInfo'],
            [[]],
            [new \DateTime()],
        ];
    }

    public function uploadExceptionsProvider(): array
    {
        return [
            [1, UploadableIniSizeException::class],
            [2, UploadableFormSizeException::class],
            [3, UploadablePartialException::class],
            [4, UploadableNoFileException::class],
            [6, UploadableNoTmpDirException::class],
            [7, UploadableCantWriteException::class],
            [8, UploadableExtensionException::class],
            [999, UploadableUploadException::class],
        ];
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::IMAGE_CLASS,
            self::ARTICLE_CLASS,
            self::FILE_CLASS,
            self::FILE_WITHOUT_PATH_CLASS,
            self::FILE_APPEND_NUMBER_CLASS,
            self::FILE_APPEND_NUMBER__RELATIVE_PATH_CLASS,
            self::FILE_WITH_ALPHANUMERIC_NAME_CLASS,
            self::FILE_WITH_SHA1_NAME_CLASS,
            self::FILE_WITH_CUSTOM_FILENAME_GENERATOR_CLASS,
            self::FILE_WITH_MAX_SIZE_CLASS,
            self::FILE_WITH_ALLOWED_TYPES_CLASS,
            self::FILE_WITH_DISALLOWED_TYPES_CLASS,
        ];
    }

    protected function assertPathEquals(string $expected, string $path, string $message = ''): void
    {
        static::assertSame($expected, $path, $message);
    }

    // Util

    private function generateUploadedFile($filePath = false, $filename = false, array $info = []): array
    {
        $defaultInfo = [
            'tmp_name' => !$filePath ? $this->testFile : $filePath,
            'name' => !$filename ? $this->testFilename : $filename,
            'size' => $this->testFileSize,
            'type' => $this->testFileMimeType,
            'error' => 0,
        ];

        return array_merge($defaultInfo, $info);
    }

    private function clearFilesAndDirectories(): void
    {
        if (is_dir($this->destinationTestDir)) {
            $iter = new \DirectoryIterator($this->destinationTestDir);

            foreach ($iter as $fileInfo) {
                if (!$fileInfo->isDot()) {
                    @unlink($fileInfo->getPathname());
                }
            }
        }
    }
}

class FakeFileInfo
{
}

class FakeFilenameGenerator implements FilenameGeneratorInterface
{
    public static function generate($filename, $extension, $object = null): string
    {
        return '123.txt';
    }
}
