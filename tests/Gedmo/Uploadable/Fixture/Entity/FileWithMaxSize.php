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
 * @Gedmo\Uploadable(allowOverwrite=true, pathMethod="getPath", callback="callbackMethod", maxSize="2")
 */
#[ORM\Entity]
#[Gedmo\Uploadable(allowOverwrite: true, pathMethod: 'getPath', callback: 'callbackMethod', maxSize: '2')]
class FileWithMaxSize
{
    /**
     * @var bool
     */
    public $callbackWasCalled = false;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, nullable: true)]
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    private $filePath;

    /**
     * @var string|null
     *
     * @ORM\Column(name="size", type="decimal")
     * @Gedmo\UploadableFileSize
     */
    #[ORM\Column(name: 'size', type: Types::DECIMAL)]
    #[Gedmo\UploadableFileSize]
    private $fileSize;

    /**
     * @var Article|null
     *
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="files")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    private $article;

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

    public function setArticle(Article $article): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setFileSize(?string $size): void
    {
        $this->fileSize = $size;
    }

    public function getFileSize(): ?string
    {
        return $this->fileSize;
    }

    public function callbackMethod(): void
    {
        $this->callbackWasCalled = true;
    }

    public function getPath(): string
    {
        return TESTS_TEMP_DIR.'/uploadable';
    }
}
