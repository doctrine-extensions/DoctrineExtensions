<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Group annotation for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * @Annotation
 * @Target("CLASS")
 */
final class SoftDeleteable extends Annotation
{
    /** @var string */
    public $fieldName = 'deletedAt';

    /** @var bool */
    public $timeAware = false;

    /** @var bool */
    public $hardDelete = true;
}
