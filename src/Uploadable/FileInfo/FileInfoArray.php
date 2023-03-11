<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\FileInfo;

/**
 * FileInfoArray
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class FileInfoArray implements FileInfoInterface
{
    /**
     * @var array<string, int|string>
     *
     * @phpstan-var array{error: int, size: int, type: string, tmp_name: string, name: string}
     */
    protected $fileInfo;

    /**
     * @param array<string, int|string> $fileInfo
     */
    public function __construct(array $fileInfo)
    {
        $keys = ['error', 'size', 'type', 'tmp_name', 'name'];

        foreach ($keys as $k) {
            if (!isset($fileInfo[$k])) {
                $msg = 'There are missing keys in the fileInfo. ';
                $msg .= 'Keys needed: '.implode(',', $keys);

                throw new \RuntimeException($msg);
            }
        }

        $this->fileInfo = $fileInfo;
    }

    public function getTmpName()
    {
        return $this->fileInfo['tmp_name'];
    }

    public function getName()
    {
        return $this->fileInfo['name'];
    }

    public function getSize()
    {
        return $this->fileInfo['size'];
    }

    public function getType()
    {
        return $this->fileInfo['type'];
    }

    public function getError()
    {
        return $this->fileInfo['error'];
    }

    public function isUploadedFile()
    {
        return true;
    }
}
