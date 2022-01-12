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
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Uploadable\Fixture\Entity\ImageWithTypedProperties;
use Gedmo\Tests\Uploadable\Stub\MimeTypeGuesserStub;
use Gedmo\Tests\Uploadable\Stub\UploadableListenerStub;

/**
 * These are tests for Uploadable behavior with different size types
 *
 * @requires PHP >= 7.4
 */
final class UploadableEntitySizeTypeTest extends BaseTestCaseORM
{
    public const IMAGE_WITH_TYPED_PROPERTIES_CLASS = ImageWithTypedProperties::class;

    /**
     * @var UploadableListenerStub
     */
    private $listener;

    /**
     * @var string
     */
    private $testFile;

    /**
     * @var string
     */
    private $destinationTestDir;

    /**
     * @var string
     */
    private $testFilename;

    /**
     * @var string
     */
    private $testFileSize;

    /**
     * @var string
     */
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
        $this->testFile = TESTS_PATH.'/data/test_for_typed_properties.txt';
        $this->destinationTestDir = TESTS_TEMP_DIR.'/uploadable';
        $this->testFilename = substr($this->testFile, strrpos($this->testFile, '/') + 1);
        $this->testFileSize = '4';
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
        $fileInfo = $this->generateUploadedFile();

        $image = new ImageWithTypedProperties();
        $image->setTitle('456');
        $this->listener->addEntityFileInfo($image, $fileInfo);

        $this->em->persist($image);
        $this->em->flush();

        $this->em->refresh($image);

        $file = $image->getFilePath();

        $this->assertPathEquals($image->getPath().'/'.$fileInfo['name'], $image->getFilePath());
        static::assertTrue(is_file($file));
        static::assertSame($fileInfo['size'], $image->getSize());
        static::assertSame($fileInfo['type'], $image->getMime());

        $this->em->remove($image);
        $this->em->flush();

        static::assertFalse(is_file($file));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::IMAGE_WITH_TYPED_PROPERTIES_CLASS,
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
