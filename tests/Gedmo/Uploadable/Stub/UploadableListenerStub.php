<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\Stub;

use Gedmo\Uploadable\UploadableListener;

final class UploadableListenerStub extends UploadableListener
{
    /**
     * @var bool
     */
    public $returnFalseOnMoveUploadedFile = false;

    public function doMoveFile($source, $dest, $isUploadedFile = true): bool
    {
        return $this->returnFalseOnMoveUploadedFile ? false : parent::doMoveFile($source, $dest, false);
    }
}
