<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\Stub;

use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;

class MimeTypeGuesserStub implements MimeTypeGuesserInterface
{
    protected $mimeType;

    public function __construct($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function guess($path)
    {
        return $this->mimeType;
    }
}
