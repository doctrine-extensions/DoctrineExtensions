<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(pathMethod="getPath")
 */
#[ORM\Entity]
#[Gedmo\Uploadable(pathMethod: 'getPath')]
class ImageWithTypedProperties
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string")
     */
    #[ORM\Column(name: 'title', type: Types::STRING)]
    private ?string $title = null;

    /**
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING, nullable: true)]
    #[Gedmo\UploadableFilePath]
    private ?string $filePath = null;

    /**
     * @ORM\Column(name="size", type="decimal", nullable=true)
     * @Gedmo\UploadableFileSize
     */
    #[ORM\Column(name: 'size', type: Types::DECIMAL, nullable: true)]
    #[Gedmo\UploadableFileSize]
    private ?string $size = null;

    /**
     * @ORM\Column(name="mime_type", type="string", nullable=true)
     * @Gedmo\UploadableFileMimeType
     */
    #[ORM\Column(name: 'mime_type', type: Types::STRING, nullable: true)]
    #[Gedmo\UploadableFileMimeType]
    private ?string $mime = null;

    private bool $useBasePath = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getPath(?string $basePath = null): string
    {
        if ($this->useBasePath) {
            return $basePath.'/abc/def';
        }

        return TESTS_TEMP_DIR.'/uploadable';
    }

    public function setMime(?string $mime): void
    {
        $this->mime = $mime;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setSize(?string $size): void
    {
        $this->size = $size;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setUseBasePath(bool $useBasePath): void
    {
        $this->useBasePath = $useBasePath;
    }
}
