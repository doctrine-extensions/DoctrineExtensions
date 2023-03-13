<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class Uploadable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var array<string, mixed>
     */
    private $fileInfo;

    /**
     * @var float
     */
    private $size;

    /**
     * @var string
     */
    private $path;

    public function getPath(): string
    {
        return $this->path;
    }

    public function callbackMethod(): void
    {
    }
}
