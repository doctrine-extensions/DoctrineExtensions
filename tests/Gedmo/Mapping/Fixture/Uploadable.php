<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Uploadable\Mapping\Validator;

#[ORM\Entity]
#[Gedmo\Uploadable(allowOverwrite: true, appendNumber: true, path: '/my/path', pathMethod: 'getPath', callback: 'callbackMethod', filenameGenerator: Validator::FILENAME_GENERATOR_SHA1, maxSize: '1500', allowedTypes: 'text/plain,text/css', disallowedTypes: 'video/jpeg,text/html')]
class Uploadable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mime', type: Types::STRING)]
    #[Gedmo\UploadableFileMimeType]
    private ?string $mimeType = null;

    /**
     * @var array<string, mixed>
     */
    private $fileInfo;

    #[ORM\Column(name: 'size', type: Types::DECIMAL)]
    #[Gedmo\UploadableFileSize]
    private ?string $size = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'path', type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    private ?string $path = null;

    public function getPath(): string
    {
        return $this->path;
    }

    public function callbackMethod(): void
    {
    }
}
