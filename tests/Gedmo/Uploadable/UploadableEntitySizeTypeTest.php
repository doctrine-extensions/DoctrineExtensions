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
use Gedmo\Uploadable\Mapping\Validator;

/**
 * This test is for Uploadable behavior with typed properties
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
    private $destinationTestDir;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->listener = new UploadableListenerStub();
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/plain'));

        $evm->addEventSubscriber($this->listener);
        $config = $this->getDefaultConfiguration();
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);

        $this->destinationTestDir = TESTS_TEMP_DIR.'/uploadable';

        $this->clearFilesAndDirectories();

        Validator::validatePath($this->destinationTestDir);
    }

    protected function tearDown(): void
    {
        $this->clearFilesAndDirectories();
    }

    public function testUploadableEntity(): void
    {
        $testFile = TESTS_PATH.'/data/test_for_typed_properties.txt';
        $testFilename = substr($testFile, strrpos($testFile, '/') + 1);
        $testFileSize = 4;
        $testFileMimeType = 'text/plain';

        $fileInfo = [
            'tmp_name' => $testFile,
            'name' => $testFilename,
            'size' => $testFileSize,
            'type' => $testFileMimeType,
            'error' => 0,
        ];

        $image = new ImageWithTypedProperties();
        $image->setTitle('456');
        $this->listener->addEntityFileInfo($image, $fileInfo);

        $this->em->persist($image);
        $this->em->flush();

        $this->em->refresh($image);

        $file = $image->getFilePath();

        $this->assertPathEquals($image->getPath().'/'.$testFilename, $image->getFilePath());
        static::assertTrue(is_file($file));
        static::assertSame((string) $testFileSize, $image->getSize());
        static::assertSame($testFileMimeType, $image->getMime());

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
