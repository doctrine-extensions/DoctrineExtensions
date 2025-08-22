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
    public function getTmpName()
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getName()
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getSize()
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getType()
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getError()
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function isUploadedFile()
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
