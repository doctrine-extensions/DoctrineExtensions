<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

class Uploadable
{
    private ?int $id = null;

    private ?string $mimeType = null;

    /**
     * @var array<string, mixed>
     */
    private array $fileInfo = [];

    private ?float $size = null;

    private ?string $path = null;

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function callbackMethod(): void {}
}
