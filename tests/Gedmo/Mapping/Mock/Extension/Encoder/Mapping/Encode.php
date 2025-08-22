<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping;

use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * @Annotation
 *
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Encode implements GedmoAnnotation
{
    /**
     * @var string
     */
    public $type = 'md5';

    /**
     * @var string|null
     */
    public $secret;

    public function __construct(string $type = 'md5', ?string $secret = null)
    {
        $this->type = $type;
        $this->secret = $secret;
    }
}
