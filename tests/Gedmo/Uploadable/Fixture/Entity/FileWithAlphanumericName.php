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
use Gedmo\Uploadable\Mapping\Validator;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(pathMethod="getPath", filenameGenerator="ALPHANUMERIC", appendNumber=true)
 */
#[ORM\Entity]
#[Gedmo\Uploadable(pathMethod: 'getPath', filenameGenerator: Validator::FILENAME_GENERATOR_ALPHANUMERIC, appendNumber: true)]
class FileWithAlphanumericName
{
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
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING, nullable: true)]
    #[Gedmo\UploadableFilePath]
    private $filePath;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getPath(): string
    {
        return TESTS_TEMP_DIR.'/uploadable';
    }
}
