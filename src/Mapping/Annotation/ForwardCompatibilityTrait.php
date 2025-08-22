<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

/**
 * @todo Remove this trait when support for array based attributes is removed.
 *
 * @internal
 */
trait ForwardCompatibilityTrait
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $args
     * @param mixed                $value
     *
     * @return mixed
     */
    private function getAttributeValue(array $data, string $attributeName, array $args, int $argumentNum, $value)
    {
        if (array_key_exists($argumentNum, $args)) {
            return $args[$argumentNum];
        }

        if (array_key_exists($attributeName, $data)) {
            return $data[$attributeName];
        }

        return $value;
    }
}
