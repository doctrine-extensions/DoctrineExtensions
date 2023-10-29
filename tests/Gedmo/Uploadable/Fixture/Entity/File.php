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
 *
 * @Gedmo\Uploadable(allowOverwrite=true, pathMethod="getPath", callback="callbackMethod")
 */
#[ORM\Entity]
#[Gedmo\Uploadable(allowOverwrite: true, pathMethod: 'getPath', callback: 'callbackMethod')]
class File
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
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, nullable: true)]
    private ?string $title = null;

    /**
     * @ORM\Column(name="path", type="string")
     *
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    private ?string $filePath = null;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="files")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    private ?Article $article = null;

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

    public function callbackMethod(): void
    {
        $this->callbackWasCalled = true;
    }

    public function getPath(): string
    {
        return TESTS_TEMP_DIR.'/uploadable';
    }
}
