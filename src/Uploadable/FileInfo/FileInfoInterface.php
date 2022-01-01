<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\FileInfo;

/**
 * FileInfoInterface
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface FileInfoInterface
{
    /**
     * @return string|null
     */
    public function getTmpName();

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @return string|null
     */
    public function getSize();

    /**
     * @return string|null
     */
    public function getType();

    /**
     * @return int
     */
    public function getError();

    /**
     * This method must return true if the file is coming from $_FILES, or false instead.
     *
     * @return bool
     */
    public function isUploadedFile();
}
