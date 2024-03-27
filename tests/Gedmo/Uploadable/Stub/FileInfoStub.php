<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\Stub;

use Gedmo\Uploadable\FileInfo\FileInfoInterface;

final class FileInfoStub implements FileInfoInterface
{
    public function getTmpName(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getName(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getSize(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getType(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getError(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function isUploadedFile(): never
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
